<?php

namespace ExpressionEngine\Addons\Rte\Service;

use ExpressionEngine\Addons\Rte\RteHelper;

class CkeditorService {

	public $output;
	public $handle;
	public $class = 'rte-textarea';
	protected $settings;
	protected $toolset;
	private $_includedFieldResources = false;
	private $_includedConfigs;
	private $_fileTags;
	private $_pageTags;
	private $_extraTags;
	private $_sitePages;
	private $_pageData;

	public function init($settings, $toolset = null)
	{
		$this->settings = $settings;
		$this->toolset = $toolset;
		$this->includeFieldResources();
		$this->insertConfigJsById();
		return $this->handle;
	}

	protected function includeFieldResources()
	{
		if (! $this->_includedFieldResources) {
			ee()->cp->add_to_foot('<script type="text/javascript" src="' . URL_THEMES . 'rte/scripts/ckeditor/ckeditor.js"></script>');
			ee()->cp->add_to_foot('<script type="text/javascript" src="' . URL_THEMES . 'rte/scripts/rte.js"></script>');
			ee()->cp->add_to_head('<link rel="stylesheet" type="text/css" href="' . URL_THEMES . 'rte/styles/ckeditor/rte.css' . '" />');

			$action_id = ee()->db->select('action_id')
				->where('class', 'Rte')
				->where('method', 'pages_autocomplete')
				->get('actions');

			$filedir_urls = ee('Model')->get('UploadDestination')->all()->getDictionary('id', 'url');

			ee()->javascript->set_global([
				'Rte.pages_autocomplete' => ee()->functions->fetch_site_index(0, 0) . QUERY_MARKER . 'ACT=' . $action_id->row('action_id') . '&t=' . ee()->localize->now,
				'Rte.filedirUrls' => $filedir_urls
			]);

			$this->_includedFieldResources = true;
		}
	}

	public function defaultConfigSettings()
	{
		return RteHelper::defaultConfigSettings();
	}

	public function getClass()
	{
		return $this->class;
	}

	protected function insertConfigJsById()
	{
		ee()->lang->loadfile('rte');

		// starting point
		$baseConfig = RteHelper::defaultConfigSettings();

		// -------------------------------------------
		//  Editor Config
		// -------------------------------------------

		if (!$this->toolset && !empty(ee()->config->item('rte_default_toolset'))) {
			$configId = ee()->config->item('rte_default_toolset');
			$toolsetQuery = ee('Model')->get('rte:Toolset');
			$toolsetQuery->filter('toolset_type', 'ckeditor');
			if (!empty($configId)) {
				$toolsetQuery->filter('toolset_id', $configId);
			}
			$toolset = $toolsetQuery->first();
		}

		if (!empty($toolset)) {
			$configHandle = preg_replace('/[^a-z0-9]/i', '_', $toolset->toolset_name) . $toolset->toolset_id;
			$config = array_merge($baseConfig, $toolset->settings);
		} else {
			$config = $baseConfig;
			$configHandle = 'default0';
		}

		$this->handle = $configHandle;

		// skip if already included
		if (isset($this->_includedConfigs) && in_array($configHandle, $this->_includedConfigs)) {
			return $configHandle;
		}

		// language
		$language = isset(ee()->session) ? ee()->session->get_language() : ee()->config->item('deft_lang');
		$langMap = RteHelper::languageMap();
		$config['language'] = isset($langMap[$language]) ? $langMap[$language] : 'en';

		// toolbar
		if (is_array($config['toolbar'])) {
			$toolbarObject = new \stdClass();
			$toolbarObject->items = $config['toolbar'];
			$config['toolbar'] = $toolbarObject;
			$config['image'] = new \stdClass();
			$config['image']->toolbar = [
				'imageTextAlternative',
				'imageStyle:full',
				'imageStyle:side',
				'imageStyle:alignLeft',
				'imageStyle:alignCenter',
				'imageStyle:alignRight'
			];
			$config['image']->styles = [
				'full',
				'side',
				'alignLeft',
				'alignCenter',
				'alignRight'
			];
		}

		if (in_array('heading', $config['toolbar']->items)) {
			$config['heading'] = new \stdClass();
			$config['heading']->options = [
				(object) ['model' => 'paragraph', 'title' => lang('paragraph_rte')],
				(object) ['model' => 'heading1', 'view' => 'h1', 'title' => lang('heading_h1_rte'), 'class' => 'ck-heading_heading1'],
				(object) ['model' => 'heading2', 'view' => 'h2', 'title' => lang('heading_h2_rte'), 'class' => 'ck-heading_heading2'],
				(object) ['model' => 'heading3', 'view' => 'h3', 'title' => lang('heading_h3_rte'), 'class' => 'ck-heading_heading3'],
				(object) ['model' => 'heading4', 'view' => 'h4', 'title' => lang('heading_h4_rte'), 'class' => 'ck-heading_heading4'],
				(object) ['model' => 'heading5', 'view' => 'h5', 'title' => lang('heading_h5_rte'), 'class' => 'ck-heading_heading5'],
				(object) ['model' => 'heading6', 'view' => 'h6', 'title' => lang('heading_h6_rte'), 'class' => 'ck-heading_heading6']
			];
		}

		if (!empty(ee()->config->item('site_pages'))) {
			ee()->cp->add_to_foot('<script type="text/javascript">
				EE.Rte.configs.' . $configHandle . '.mention = {"feeds": [{"marker": "@", "feed": getPages, "itemRenderer": formatPageLinks, "minimumCharacters": 3}]};
			</script>');
		}

		// -------------------------------------------
		//  File Browser Config
		// -------------------------------------------

		$uploadDir = (isset($config['upload_dir']) && !empty($config['upload_dir'])) ? $config['upload_dir'] : 'all';
		unset($config['upload_dir']);

		$fileBrowserOptions = ['filepicker'];
		if (!empty(ee()->config->item('rte_file_browser'))) {
			array_unshift($fileBrowserOptions, ee()->config->item('rte_file_browser'));
		}
		$fileBrowserOptions = array_unique($fileBrowserOptions);
		foreach ($fileBrowserOptions as $fileBrowserName) {
			$fileBrowserAddon = ee('Addon')->get($fileBrowserName);
			if ($fileBrowserAddon !== null && $fileBrowserAddon->isInstalled() && $fileBrowserAddon->hasRteFilebrowser()) {
				$fqcn = $fileBrowserAddon->getRteFilebrowserClass();
				$fileBrowser = new $fqcn();
				if ($fileBrowser instanceof RteFilebrowserInterface) {
					$fileBrowser->addJs($uploadDir);

					break;
				}
			}
		}

		if (stripos($fqcn, 'filepicker_rtefb') !== false && REQ != 'CP') {
			unset($config['image']);
			$filemanager_key = array_search('filemanager', $config['toolbar']->items);
			if ($filemanager_key) {
				$items = $config['toolbar']->items;
				unset($items[$filemanager_key]);
				$config['toolbar']->items = array_values($items);
			}
		}

		$config['toolbar']->shouldNotGroupWhenFull = true;

		//link
		$config['link'] = (object) ['decorators' => [
			'openInNewTab' => [
				'mode' => 'manual',
				'label' => lang('open_in_new_tab'),
				'attributes' => [
					'target' => '_blank',
					'rel' => 'noopener noreferrer'
				]
			]
		]
		];

		// -------------------------------------------
		//  JSONify Config and Return
		// -------------------------------------------
		ee()->javascript->set_global([
			'Rte.configs.' . $configHandle => $config
		]);

		$this->_includedConfigs[] = $configHandle;

		ee()->cp->add_to_head('<style type="text/css">.ck-editor__editable_inline { min-height: ' . $config['height'] . 'px; }</style>');

		return $configHandle;
	}

	public function toolbarInputHtml($config)
	{
		$fullToolbar = RteHelper::defaultToolbars()['Full'];
		$fullToolset = [];
		foreach ($fullToolbar as $i => $tool) {
			$fullToolset[$tool] = lang($tool . '_rte');
		}

		return ee('View')->make('rte:toolbar')->render(
			[
				'buttons' => $fullToolset,
				'selection' => $config->settings['toolbar']
			]
		);
	}

}