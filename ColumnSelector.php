<?php
namespace rikcage\grid_column_select;

use Yii;
//use yii\grid\GridView;
use kartik\grid\GridView;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\View;
use yii\web\JsExpression;
use rikcage\grid_column_select\ColumnSelectorAsset;
use yii\jui\JuiAsset;

use yii\data\ActiveDataProvider;
use yii\db\ActiveQueryInterface;
use yii\db\QueryInterface;
use yii\grid\DataColumn;

class ColumnSelector extends GridView
{
	/**
     * @var array the HTML attributes for the column selector dropdown button. The following special options are
     * recognized:
     * - `label`: _string_, defaults to empty string.
     * - `icon`: _string_, defaults to `<i class="glyphicon glyphicon-list"></i>`
     * - `title`: _string_, defaults to `Select columns for export`.
     */
    public $columnSelectorOptions = [];
	
    /**
     * @var array the HTML attributes for the column selector menu list.
     */
    public $columnSelectorMenuOptions = [];
	
	//public $columns;
	public static $columns_show = [];
	
    /**
     * @var string Name of cookies the column indices for selection to be shown.
     */
	public $cookiesName = 'rk_GS';

	public $exportColumnsView = '_columns.php';
	
    /**
     * @var array the configuration of the column names in the column selector. Note: column names will be generated
     * automatically by default. Any setting in this property will override the auto-generated column names. This
     * list should be setup as `$key => $value` where:
     * - `$key`: _integer_, is the zero based index of the column as set in `$columns`.
     * - `$value`: _string_, is the column name/label you wish to display in the column selector.
     */
    public $columnSelector = [];
	
    /**
     * @var array the selected column indexes for export. If not set this will default to all columns.
     */
    public $selectedColumns;
	
    /**
     * @var array the settings for the toggle all checkbox to check / uncheck the columns as a batch. Should be setup as
     * an associative array which can have the following keys:
     * - `show`: _boolean_, whether the batch toggle checkbox is to be shown. Defaults to `true`.
     * - `label`: _string_, the label to be displayed for toggle all. Defaults to `Toggle All`.
     * - `options`: _array_, the HTML attributes for the toggle label text. Defaults to `['class'=>'kv-toggle-all']`
     */
    public $columnBatchToggleSettings = [];

    /**
     * @var array the column indexes for export that will be disabled for selection in the column selector.
     */
    public $disabledColumns = [];

    /**
     * @var array the column indexes for export that will be hidden for selection in the column selector, but will
     * still be displayed in export output.
     */
    public $hiddenColumns = [];

    /**
     * @var array the column indexes for export that will not be exported at all nor will they be shown in the column
     * selector
     */
    public $noExportColumns = [];
	
    /**
     * @var string the select columns input parameter for select form
     */
    public $exportColsParam = 'select_columns';
	
    /**
     * @var boolean whether to show a column selector to select columns for export. Defaults to `true`.
     * This is applicable only if [[asDropdown]] is set to `true`. Else this property is ignored.
     */
    public $showColumnSelector = true;
	
    /**
     * @var boolean private flag that will use $_POST [[exportRequestParam]] setting if available or use the
     * [[triggerDownload]] setting
     */
    private $_triggerDownload;
	
    /**
     * @var array the visble columns for export
     */
    protected $_visibleColumns;

    /**
     * @var array configuration settings for the Krajee dialog widget that will be used to render alerts and
     * confirmation dialog prompts
     * @see http://demos.krajee.com/dialog
     */
    public $krajeeDialogSettings = [];
	
    /**
     * @var array, the configuration of various messages that will be displayed at runtime:
     * - allowPopups: string, the message to be shown to disable browser popups for download. Defaults to `Disable any
     *   popup blockers in your browser to ensure proper download.`.
     * - confirmDownload: string, the message to be shown for confirming to proceed with the download. Defaults to `Ok
     *   to proceed?`.
     * - downloadProgress: string, the message to be shown in a popup dialog when download request is executed.
     *   Defaults to `Generating file. Please wait...`.
     * - downloadComplete: string, the message to be shown in a popup dialog when download request is completed.
     *   Defaults to `All done! Click anywhere here to close this window, once you have downloaded the file.`.
     */
    public $messages = [];

    /**
     * @var string the column selector flag parameter for export form
     */
    public $colSelFlagParam = 'column_selector_enabled';

    /**
     * @var boolean whether the column selector is enabled
     */
    protected $_columnSelectorEnabled;

    /**
     * @var string the request parameter ($_GET or $_POST) that will be submitted during export. If not set this will
     *  be auto generated. This should be unique for each export menu widget (for multiple export menu widgets on
     *  same page).
     */
    public $exportRequestParam;

    /**
     * @var boolean flag to identify if download is triggered
     */
    public $triggerDownload = false;
	
	
    public function init()
    {
		$this->cookiesName .= '_'.Yii::$app->controller->module->id.'/'.Yii::$app->controller->id.'/'.Yii::$app->controller->action->id;
		self::$columns_show = $this->columns;
		
		//$this->view->registerJsFile('path/to/myfile', ['depends' => [\yii\web\JqueryAsset::className()]]);
		ColumnSelectorAsset::register($this->view);
		JuiAsset::register($this->view);
        $this->initSettings();
        parent::init();
	}

    public function run()
    {
        $this->initColumnSelector();
        $this->registerAssets();
        echo $this->renderColumnSelector();
		
        $this->setVisibleColumns();
		self::$columns_show = $this->_visibleColumns;
	}

    public static function getShowColumns()
    {
		return self::$columns_show;
	}
	
    /**
     * Initialize export menu settings
     */
    protected function initSettings()
    {
        if (empty($this->options['id'])) {
            $this->options['id'] = $this->getId();
        }
        if (empty($this->exportRequestParam)) {
            $this->exportRequestParam = 'selectCol_' . $this->options['id'];
        }
        $path = '@vendor/rikcage/yii2-grid-column-select/views';
        if (!isset($this->exportColumnsView)) {
            $this->exportColumnsView = "{$path}/_columns";
        }
        $this->_columnSelectorEnabled = $this->showColumnSelector;
        $request = Yii::$app->request;
        $this->_triggerDownload = $request->post($this->exportRequestParam, $this->triggerDownload);
        if ($this->_triggerDownload) {
            $this->_columnSelectorEnabled = $request->post($this->colSelFlagParam, $this->_columnSelectorEnabled);
            $this->initSelectedColumns();
        }else{
			$cookies = Yii::$app->request->cookies;
			$this->selectedColumns = $cookies->getValue($this->cookiesName);
		}
    }
	
    /**
     * Initialize columns selected for export
     */
    protected function initSelectedColumns()
    {
        if (!$this->_columnSelectorEnabled) {
            return;
        }
        $expCols = Yii::$app->request->post($this->exportColsParam, '');
        $this->selectedColumns = empty($expCols) ? array_keys($this->columnSelector) : Json::decode($expCols);
    }

    protected function initColumnSelector()
    {
        $selector = [];
        Html::addCssClass($this->columnSelectorOptions, ['btn', 'btn-default', 'dropdown-toggle']);
        $header = ArrayHelper::getValue($this->columnSelectorOptions, 'header', Yii::t('app', 'Select Columns'));
        $this->columnSelectorOptions['header'] = (!isset($header) || $header === false) ? '' :
            '<li class="dropdown-header">' . $header . '</li><li class="kv-divider"></li>';
        $id = $this->options['id'] . '-cols';
        Html::addCssClass($this->columnSelectorMenuOptions, 'dropdown-menu kv-checkbox-list');
        $this->columnSelectorMenuOptions = array_replace_recursive(
            [
                'id' => $id . '-list',
                'role' => 'menu',
                'aria-labelledby' => $id,
            ],
            $this->columnSelectorMenuOptions
        );
        $this->columnSelectorOptions = array_replace_recursive(
            [
                'id' => $id,
                'icon' => '<i class="glyphicon glyphicon-list"></i>',
                //'title' => Yii::t('kvexport', 'Select columns to export'),
                'title' => Yii::t('app', 'Select columns to export'),
                'type' => 'button',
                'data-toggle' => 'dropdown',
                'aria-haspopup' => 'true',
                'aria-expanded' => 'false',
            ],
            $this->columnSelectorOptions
        );
        foreach ($this->columns as $key => $column) {
            $selector[$key] = $this->getColumnLabel($key, $column);
        }
        $this->columnSelector = array_replace($selector, $this->columnSelector);
		
        if (!isset($this->selectedColumns)) {
            $keys = array_keys($this->columnSelector);
            $this->selectedColumns = array_combine($keys, $keys);
        }
		
	}
	
    public function renderColumnSelector()
    {
        return $this->render(
            $this->exportColumnsView,
            [
                //'isBs4' => $this->isBs4(), // Validate if Bootstrap 4.x version
                'isBs4' => false, // Validate if Bootstrap 4.x version
                'options' => $this->columnSelectorOptions,
                'menuOptions' => $this->columnSelectorMenuOptions,
                'columnSelector' => $this->columnSelector,
                'batchToggle' => $this->columnBatchToggleSettings,
                'selectedColumns' => $this->selectedColumns,
                'disabledColumns' => $this->disabledColumns,
                'hiddenColumns' => $this->hiddenColumns,
                'noExportColumns' => $this->noExportColumns,
            ]
        );
    }
	
    /**
     * Fetches the column label
     *
     * @param integer $key
     * @param Column $column
     *
     * @return string
     */
    protected function getColumnLabel($key, $column)
    {
        $key++;
        $label = Yii::t('app', 'Column') . ' ' . $key;
        if (isset($column->label)) {
            $label = $column->label;
        } elseif (isset($column->header)) {
            $label = $column->header;
        } elseif (isset($column->attribute)) {
            $label = $this->getAttributeLabel($column->attribute);
        } elseif (!$column instanceof DataColumn) {
            $class = explode('\\', get_class($column));
            $label = Inflector::camel2words(end($class));
        }
        return trim(strip_tags(str_replace(['<br>', '<br/>'], ' ', $label)));
    }

    /**
     * Generates the attribute label
     *
     * @param string $attribute
     *
     * @return string
     */
    protected function getAttributeLabel($attribute)
    {
        /**
         * @var Model $model
         */
        $provider = $this->dataProvider;
        if ($provider instanceof ActiveDataProvider && $provider->query instanceof ActiveQueryInterface) {
            $model = new $provider->query->modelClass;
            return $model->getAttributeLabel($attribute);
        } elseif ($provider instanceof ActiveDataProvider && $provider->query instanceof QueryInterface) {
            return Inflector::camel2words($attribute);
        } else {
            $models = $provider->getModels();
            if (($model = reset($models)) instanceof Model) {
                return $model->getAttributeLabel($attribute);
            } else {
                return Inflector::camel2words($attribute);
            }
        }
    }
	
    public function setVisibleColumns()
    {
		
		$columns = [];
		foreach ($this->selectedColumns as $index => $key) {
			if(empty($this->columns[$key])){
				continue;
			}
			$column = $this->columns[$key];
			$isActionColumn = $column instanceof ActionColumn;
			$isNoExport = in_array($key, $this->noExportColumns) || !$this->showColumnSelector;
            if (!empty($column->hiddenFromExport) || $isActionColumn || $isNoExport) {
                continue;
            }
			$columns[] = self::$columns_show[$key];
		}
		if(!count($columns)){
			$columns = [[]];
		}
		$this->_visibleColumns = $columns;
		$value = $this->selectedColumns;
		$cookies = Yii::$app->response->cookies;
		$cookies->add(new \yii\web\Cookie([
			'name' => $this->cookiesName,
			'value' => $this->selectedColumns,
		]));
		
    }
	
    protected function registerAssets()
    {
        $view = $this->getView();
        $this->messages += [
            'allowPopups' => Yii::t(
                'app',
                'Disable any popup blockers in your browser to ensure proper download.'
            ),
            'confirmDownload' => Yii::t('app', 'Ok to proceed?'),
            'downloadProgress' => Yii::t('app', 'Generating the export file. Please wait...'),
            'downloadComplete' => Yii::t(
                'app',
                'Request submitted! You may safely close this dialog after saving your downloaded file.'
            ),
        ];
        $options = [
            'messages' => $this->messages,
            'colSelFlagParam' => $this->colSelFlagParam,
            'colSelEnabled' => $this->_columnSelectorEnabled ? 1 : 0,
            'exportRequestParam' => $this->exportRequestParam,
            'exportColsParam' => $this->exportColsParam,
        ];
        if ($this->_columnSelectorEnabled) {
            $options['colSelId'] = $this->columnSelectorOptions['id'];
        }
        $options = Json::encode($options);
        $menu = 'rk_selcol_' . hash('crc32', $options);
        $view->registerJs("var {$menu} = {$options};\n", View::POS_HEAD);
        $script = '';
		$options = Json::encode([
			'settings' => new JsExpression($menu),
			'alertMsg' => Yii::t('app', 'The HTML export file will be generated for download.'),
		]);
		$script .= "jQuery('#{$this->options['id']}-cols-submit').exportdata({$options});\n";
        if ($this->_columnSelectorEnabled) {
            $id = $this->columnSelectorMenuOptions['id'];
            $script .= "jQuery('#{$id}').exportcolumns({});\n";
        }
        if (!empty($script) && isset($this->pjaxContainerId)) {
            $script .= "jQuery('#{$this->pjaxContainerId}').on('pjax:complete', function() {
                {$script}
            });\n";
        }
		$script .= '
			$( function() {
				$( "#'.$this->columnSelectorMenuOptions['id'].'" ).sortable();
				$( "#'.$this->columnSelectorMenuOptions['id'].'" ).disableSelection();
			} );
		';
        $view->registerJs($script);
    }
}
