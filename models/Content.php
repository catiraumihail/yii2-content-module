<?php

namespace ut8ia\contentmodule\models;

use Yii;
use yii\db\ActiveRecord;
use pendalf89\filemanager\behaviors\MediafileBehavior;
use ut8ia\multylang\models\Lang;
use ut8ia\contentmodule\models\ContentRubrics;
use ut8ia\contentmodule\models\ContentSections;
use ut8ia\contentmodule\models\Tags;
use ut8ia\contentmodule\models\TagsLink;
use common\models\User;
/**
 * This is the model class for table "content".
 *
 * @property integer $id
 * @property string $name
 * @property string $slug
 * @property string $text
 * @property string $lang_id
 * @property string $date
 * @property string $rubric_id
 * @property string $author_id
 * @property string $section_id
 * @property string $stick
 *
 */
class Content extends ActiveRecord
{

    /**
     * @inheritdoc
     */
    public $SystemTags;
    public $NavTags;

    public static function tableName()
    {
        return 'contentmanager_content';
    }


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'text', 'lang_id', 'rubric_id','section_id'], 'required'],
            [['text', 'slug', 'stick'], 'string'],
            [['date', 'author_id', 'SystemTags', 'NavTags'], 'safe'],
            [['section_id','lang_id','rubric_id'],'integer'],
            [['name'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'slug' => 'slug',
            'text' => 'Text',
            'date' => 'Date',
            'author_id' => 'Author',
            'rubric_id' => 'Theme',
            'section_id' =>'Section',
            'stick' => Yii::t('main', 'stick')
        ];
    }

    public function behaviors()
    {
        return [
            'slug' => [
                'class' => 'common\behaviors\Slug',
                'in_attribute' => 'name',
                'out_attribute' => 'slug',
                'translit' => true
            ],
            'mediafile' => [
                'class' => MediafileBehavior::className(),
                'name' => 'content',
                'attributes' => [
                    'thumbnail',
                ]
            ]
        ];
    }


    public function getAuthor()
    {
        return $this->hasOne(User::class, ['id' => 'author_id']);
    }

    public function getRubric()
    {
        return $this->hasOne(ContentRubrics::class, ['id' => 'rubric_id']);
    }


    public function getSection(){
        return $this->hasOne(ContentSections::class, ['id' => 'section_id']);
    }


    public function getLanguage()
    {
        return $this->hasOne(Lang::class, ['id' => 'lang_id']);
    }

    public function getArticleTags()
    {
        return $this->hasMany(TagsLink::class, ['link_id' => 'id']);
    }

    public function getTags()
    {
        return $this->hasMany(Tags::class, ['id' => 'tag_id'])
            ->viaTable(TagsLink::tableName(), ['link_id' => 'id']);
    }

    public function getSystemTags()
    {
        return $this->getLinkedTagsByType($this->id, 1, 0, null);
    }


    public function getNavTags()
    {
        return $this->getLinkedTagsByType($this->id, 2, 0, null);
    }



    public function beforeSave($insert)
    {

        if (parent::beforeSave($insert)) {
            // set author_id only for new records
            if ($this->id > 0) {
                unset($this->author_id);
            } else {
                // set current user as author
                $this->author_id = \Yii::$app->user->identity->id;
            }
            return true;
        } else {
            return false;
        }
    }


    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if (is_array($this->NavTags) and is_array($this->SystemTags)) {
            $tags = array_merge($this->NavTags, $this->SystemTags);
        } elseif (!is_array($this->NavTags)) {
            $tags = $this->SystemTags;
        } else {
            $tags = $this->NavTags;
        }
        $tagsLink = new TagsLink();
        $tagsLink->linkTag($this->id, $tags, 1);
    }


    public function getDefault()
    {
        return Content::findOne(0);
    }

    /**
     * @param null $rubric_id
     * @param null $limit
     * @return $this|array|null|\yii\db\ActiveRecord|\yii\db\ActiveRecord[]
     */
    public function getLast($rubric_id = null, $limit = null)
    {
        $ans = Content::find()
            ->orderBy('date DESC')
            ->where(['=', 'rubric_id', $rubric_id])
            ->andWhere(['=', '`contentmanager_content`.`lang_id`', Lang::getCurrent()->id]);
        // if set limit - return all 
        $ans = (isset($limit)) ? $ans->limit($limit)->all() : $ans->one();

        return $ans;
    }

    /**
     * @param $rubric_id
     * @param null $limit
     * @return $this
     */
    public function byRubric($rubric_id, $limit = null)
    {
        $ans = Content::find()
            ->where(['=', 'rubric_id', $rubric_id])
            ->andWhere(['=', '`contentmanager_content`.`lang_id`', Lang::getCurrent()->id])
            ->orderBy('date DESC');
        $ans = ((int)$limit) ? $ans->limit($limit) : $ans;
        $ans = $ans->all();
        return $ans;
    }

    /**
     * @param $rubric_id
     * @param $tag
     * @param $tag_type
     * @param null $limit
     * @return $this
     */
    public function byRubricTag($rubric_id, $tag, $tag_type, $limit = null)
    {
        $ans = Content::find()
            ->from(['contentmanager_tags'])
            ->join('INNER JOIN', 'contentmanager_tags_link', '`contentmanager_tags_link`.`tag_id` = `contentmanager_tags`.`id`')
            ->join('INNER JOIN', 'contentmanager_content', '`contentmanager_content`.`id` = `contentmanager_tags_link`.`link_id`')
            ->select('`contentmanager_content`.*')
            ->where(['=', '`contentmanager_tags`.`name`', $tag])
            ->andWhere(['=', 'rubric_id', $rubric_id])
            ->andWhere(['=', '`contentmanager_tags`.`type`', $tag_type])
            ->andWhere(['=', '`contentmanager_content`.`lang_id`', Lang::getCurrent()->id])
            ->orderBy('date DESC');

        $ans = ((int)$limit) ? $ans->limit($limit) : $ans;
        $ans = $ans->all();
        return $ans;
    }

    /**
     * @param $rubric_id
     * @param null $limit
     * @return $this
     */
    public function StickByRubric($rubric_id, $limit = null)
    {
        $ans = Content::find()
            ->where(['=', 'rubric_id', $rubric_id])
            ->andWhere(['=', '`contentmanager_content`.`lang_id`', Lang::getCurrent()->id])
            ->andWhere(['=', 'stick', 'true'])
            ->orderBy('date DESC');
        $ans = ((int)$limit) ? $ans->limit($limit) : $ans;
        $ans = $ans->all();
        return $ans;
    }

    /**
     * @param $tag
     * @return array|null|\yii\db\ActiveRecord|static
     */
    public function byTag($tag)
    {
        $ans = Content::find()
            ->from(['contentmanager_tags'])
            ->join('INNER JOIN', 'contentmanager_tags_link', '`contentmanager_tags_link`.`tag_id` = `contentmanager_tags`.`id`')
            ->join('INNER JOIN', 'content', '`contentmanager_content`.`id` = `contentmanager_tags_link`.`link_id`')
            ->select('`contentmanager_content`.*')
            ->where(['=', '`contentmanager_tags`.`name`', $tag])
            ->andWhere(['=', '`contentmanager_content`.`lang_id`', Lang::getCurrent()->id])
            ->orderBy('date DESC')
            ->one();

        if (!isset($ans->id)) {
            $ans = Content::getDefault();
        }
        return $ans;
    }


    /**
     * @param $article_id
     * @param $tag_type
     * @param $asArray
     * @param null $limit
     * @return $this|string
     */
    public function getLinkedTagsByType($article_id, $tag_type, $asArray, $limit = null)
    {
        $out = "";
        $ans = Tags::find()
            ->from(['contentmanager_content'])
            ->join('INNER JOIN', 'contentmanager_tags_link', '`contentmanager_tags_link`.`link_id` = `contentmanager_content`.`id`')
            ->join('INNER JOIN', 'contentmanager_tags', '`contentmanager_tags`.`id` = `contentmanager_tags_link`.`tag_id`')
            ->select(['`contentmanager_tags`.*'])
            ->indexBy('id')
            ->where(['=', '`contentmanager_content`.`id`', $article_id])
            ->andWhere(['=', '`contentmanager_tags`.`type`', $tag_type]);
        if ((int)$limit) {
            $ans->limit($limit);
        }
        if ($asArray) {
            $ans->asArray();
        }
        $ans = $ans->all();

        if ($asArray) {
            foreach ($ans as $ind => $val) {
                $out[$ind] = $val['name'];
            }
            return $out;
        }
        return $ans;
    }

    /**
     * tags collection for the content
     * @return array
     */
    public function collection()
    {
        return Content::find()
            ->asArray()
            ->with('tags')
            ->where(['=', '`contentmanager_content`.`lang_id`', Lang::getCurrent()->id])
            ->all();
    }

    /**
     * @return array
     */
    public function selector()
    {
        return Content::find()
            ->select('name')
            ->indexBy('id')
            ->column();
    }

}
