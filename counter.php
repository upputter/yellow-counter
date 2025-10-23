<?php

class YellowCounter
{
	const VERSION = "0.1.1";
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
			$output = '<table id="counterStats"><thead><tr><th data-sort="string">Title</th><th data-sort="string">Location</th><th data-sort="int">Views</th></tr></thead><tbody>';
			$counterData = $this->loadViewCounter();
			foreach ($counterData as $counterLocation => $counterValue) {
				$output .= '<tr><td><a href="' . $this->yellow->content->find($counterLocation)->getUrl() . '">' . $this->yellow->content->find($counterLocation)->get('title') . '</a></td><td>' . $counterLocation . '</td><td>' . $counterValue . '</td></tr>';
			}
			$output .= '</tbody></table>';
		}
		return $output;
	}

	public function onParseContentHtml($page, $html)
	{
		if (str_contains($html, '<table id="counterStats">')) {
			$html .= '<script> /* https://github.com/oleksavyshnivsky/tablesort */ function getCellIndex(t){var a=t.parentNode,r=Array.from(a.parentNode.children).indexOf(a);let s=0;for(let e=0;e<a.cells.length;e++){var l=a.cells[e].colSpan;if(s+=l,0===r){if(e===t.cellIndex)return s-1}else if(!isNaN(parseInt(t.dataset.sortCol)))return parseInt(t.dataset.sortCol)}return s-1}let is_sorting_process_on=!1,delay=100;function tablesort(e){if(is_sorting_process_on)return!1;is_sorting_process_on=!0;var t=e.currentTarget.closest("table"),a=getCellIndex(e.currentTarget),r=e.currentTarget.dataset.sort,s=t.querySelector("th[data-dir]"),s=(s&&s!==e.currentTarget&&delete s.dataset.dir,e.currentTarget.dataset.dir?"asc"===e.currentTarget.dataset.dir?"desc":"asc":e.currentTarget.dataset.sortDefault||"asc"),l=(e.currentTarget.dataset.dir=s,[]),o=t.querySelectorAll("tbody tr");let n,u,c,d,v;for(j=0,jj=o.length;j<jj;j++)for(n=o[j],l.push({tr:n,values:[]}),v=l[j],c=n.querySelectorAll("th, td"),i=0,ii=c.length;i<ii;i++)u=c[i],d=u.dataset.sortValue||u.innerText,"int"===r?d=parseInt(d):"float"===r?d=parseFloat(d):"date"===r&&(d=new Date(d)),v.values.push(d);l.sort("string"===r?"asc"===s?(e,t)=>(""+e.values[a]).localeCompare(t.values[a]):(e,t)=>-(""+e.values[a]).localeCompare(t.values[a]):"asc"===s?(e,t)=>isNaN(e.values[a])||isNaN(t.values[a])?isNaN(e.values[a])?isNaN(t.values[a])?0:-1:1:e.values[a]<t.values[a]?-1:e.values[a]>t.values[a]?1:0:(e,t)=>isNaN(e.values[a])||isNaN(t.values[a])?isNaN(e.values[a])?isNaN(t.values[a])?0:1:-1:e.values[a]<t.values[a]?1:e.values[a]>t.values[a]?-1:0);const N=document.createDocumentFragment();return l.forEach(e=>N.appendChild(e.tr)),t.querySelector("tbody").replaceChildren(N),setTimeout(()=>is_sorting_process_on=!1,delay),!0}Node.prototype.tsortable=function(){this.querySelectorAll("thead th[data-sort], thead td[data-sort]").forEach(e=>e.onclick=tablesort)}; </script>';
			$html .= '<style>[data-sort]:hover { cursor: pointer; } [data-dir="asc"]:after { content: "\00A0↗"; } [data-dir="desc"]:after { content: "\00A0↘"; }</style>';
			$html .= '<script>document.querySelector("#counterStats").tsortable()</script>';
		}
		return $html;
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

if (!function_exists('str_contains')) {
	function str_contains(string $haystack, string $needle)
	{
		return empty($needle) || strpos($haystack, $needle) !== false;
	}
}
