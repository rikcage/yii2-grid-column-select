Grid column select for kartik grid, Extension for Yii 2.0 Framework
========================================
Show / hide change position selected columns in grid, extension for Yii 2.0 Framework

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

To install, either run

```
$ php composer.phar require rikcage/yii2-grid-column-select "*"
```

or add

```
"rikcage/yii2-grid-column-select": "*"
```

to the ```require``` section of your `composer.json` file.

Usage
-----
in your views

```
use rikcage\grid_column_select\ColumnSelector;

use kartik\grid\GridView;
or
use yii\grid\GridView;

	<?php
		$columns = [
			['class' => 'yii\grid\SerialColumn'],
			['class' => 'yii\grid\ActionColumn'],
			'id',
			'title',
		];
	?>

	<?php 
		$grid = ColumnSelector::widget([
				'dataProvider' => $dataProvider,
				'filterModel' => $searchModel,
				'columns' => $columns,
				'defaultShowColumns'=>[1, 0,], // default settinfs show  first title after id
		]);
		echo $grid;
		$columns = ColumnSelector::getShowColumns();
	?>

	<?php
		echo GridView::widget([
			'dataProvider' => $dataProvider,
			'filterModel' => $searchModel,
			'columns' => $columns,
		]);
	?>
```

if you want save selection of columns which user selected, in db you should add in your settings file (/common/config/main.php)

```
	'modules' => [
    ...
		'columnSelector' => [
			'class' => 'rikcage\grid_column_select\ColumnSelector',
			'params' => [
				'userClass' => 'common\models\User', // models of user settings
				'userid' => 'id', // field name which have user identity Yii::$app->user->identity->id
				'settings_field' => 'params', // field name for save parameters which user selected
			]
		],
    
	],
```
