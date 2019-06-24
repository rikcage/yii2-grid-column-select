<?php
namespace rikcage\grid_column_select;

use Yii;
use yii\web\AssetBundle;
 
class ColumnSelectorAsset extends AssetBundle
{
    public $sourcePath = '@vendor/rikcage/yii2-grid-column-select/assets';

    public $js = [
        'js/rk-select-data.min.js',
        'js/rk-select-columns.min.js',
    ];

    public $css = [
        'css/rk-select-columns.min.css',
    ];
	
    public $depends = [
        'yii\web\JqueryAsset',
    ];
}
