<?php
namespace rikcage\grid_column_select;

use Yii;
use yii\web\AssetBundle;
 
class ColumnSelectorAsset extends AssetBundle
{
    public $sourcePath = '@vendor/rikcage/yii2-grid-column-select/assets';

    public $js = [
        'js/kv-export-data.min.js',
        'js/kv-export-columns.min.js',
    ];

    public $css = [
        'css/kv-export-columns.css',
    ];
	
    public $depends = [
        'yii\web\JqueryAsset',
    ];
}
