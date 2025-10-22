<?php

class YellowCounter
{
	const VERSION = "0.1.0";
	public $yellow;

	public function onLoad($yellow)
	{
		$this->yellow = $yellow;
		$this->yellow->system->setDefault("counterLogFile", "viewcounter.ini");
		$this->yellow->system->setDefault("counterMessageTemplate", "@counter");
	}

	public function onParseContentElement($page, $name, $text, $attributes, $type)
	{
		$output = null;
		$displayText = $this->yellow->system->get('counterMessageTemplate');
		list($displayType) = $this->yellow->toolbox->getTextArguments($text);
		if ($name == "counter" && ($type == "block" || $type == "inline") and $page->isActive()) {
			$viewCount = 1;
			$location = $page->getLocation();
			$counterData = $this->loadViewCounter();
			if ($counterData->isExisting($location)) {
				$viewCount = $counterData->get($location) + 1;
				$counterData->set($location, $viewCount);
			} else {
				$counterData->set($location, $viewCount);
			}
			$this->saveViewCounter($counterData);
			$output = preg_replace("/@counter/i", $viewCount, $displayText);			
		}
		if ($displayType == 'hidden') return null;

		if ($name == "counterStats" && ($type == "block" || $type == "inline")) {
			$this->yellow->system->modified = time();
			$output = '<table id="counterStats"><thead><tr><th>Title</th><th>Location</th><th>Views</th></tr></thead><tbody> ';
			$counterData = $this->loadViewCounter();
			foreach ($counterData AS $counterLocation => $counterValue) {
				$output .= '<tr><td><a href="' . $this->yellow->content->find($counterLocation)->getUrl() . '">' . $this->yellow->content->find($counterLocation)->get('title') . '</a></td><td>' . $counterLocation . '</td><td>' . $counterValue . '</td></tr>';
			}
			$output .= '</tbody></table>';
		}
		return $output;
	}

	public function saveViewCounter($counterData)
	{
		$fileName = $this->yellow->system->get("coreExtensionDirectory") . $this->yellow->system->get('counterLogFile');
		$fileData = $this->yellow->toolbox->readFile($fileName);
		$fileData = $this->yellow->toolbox->setTextSettings($fileData, '', '', $counterData);
		$this->yellow->toolbox->writeFile($fileName, $fileData);
	}

	public function loadViewCounter()
	{
		$fileName = $this->yellow->system->get("coreExtensionDirectory") . $this->yellow->system->get('counterLogFile');
		$fileData = $this->yellow->toolbox->readFile($fileName);
		$data = $this->yellow->toolbox->getTextSettings($fileData, '');
		return $data;
	}
}
