<?php

use yii\helpers\Html;
use yii\grid\GridView;
use ut8ia\multylang\models\Lang;

/* @var $this yii\web\View */
/* @var $searchModel ut8ia\contentmodule\models\ContentSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Content Rubrics');
?>
<div class="content-rubrics-index">
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?= Html::a(Yii::t('app', 'Create Content Rubrics'), ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        //    'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\ActionColumn',
                'contentOptions' => ['class' => 'col-sm-1 small text-right', 'nowrap' => 'nowrap'],],

            [
                'contentOptions' => ['class' => 'col-sm-5 small text-left'],
                'attribute' => 'section_id',
                'format' => 'html',
                'value' => function($model) {
                    return $model->section->name;
                },
            ],
            [
                'contentOptions' => ['class' => 'col-sm-6 small text-left'],
                'attribute' => 'Name',
                'format' => 'html',
                'value' => function($model) {
                    $name = Lang::getCurrent()->url;
                    $property_name = 'name_' . $name;
                    return $model->$property_name;
                },
            ]
        ],
    ]); ?>

</div>
