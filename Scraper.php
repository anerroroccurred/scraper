<?php
/**
 * Created by PhpStorm.
 * User: kuba
 * Date: 2015-03-18
 * Time: 14:55
 */

class Scraper {

	public $url;
	public $headers;
	public $source;
	public $xPath;
	public $baseUrl;
	public $pathToPdf;
	private $parsedUrl = array();

    public function __construct($url, $action, $path = '') {
        $this->url = $url;
        switch ($action){
            case 'get':
                $this->source = $this->curlGet($this->url);
                break;
            case 'post':
                $this->source = $this->curlPost($this->url, $this->headers);
                break;
            case 'pdf':
                $this->pathToPdf = $this->curlGetPdf($this->url, $path);
                break;
            default:
                $this->source = null;
        }
        $this->xPath = $this->getXPath($this->source);
        $this->parsedUrl = parse_url($this->url);
        @$this->baseUrl = $this->parsedUrl['scheme'] . '://' . $this->parsedUrl['host'];
    }

    public function curlGet($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_URL, $url);
        $results = curl_exec($ch);
        curl_close($ch);
        return $results;
    }

    public function curlPost($url, $headers = array()) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_COOKIESESSION, TRUE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        $results = curl_exec($ch);
        curl_close($ch);
        return $results;
    }

    public function curlGetPdf($url, $destination) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_URL, $url);
        $data = curl_exec($ch);
        curl_close($ch);
        $file = fopen($destination, "w+");
        fputs($file, $data);
        if (fclose($file)){
            return $destination;
        }
    }
	
	public function getEmailsFromLinks() {
		$links = $this->xPath->query('//a');
		$links_array = array();
		if ($links->length > 0) {
			for ($i = 0; $i < $links->length; $i++) {
				$link = $links->item($i)->attributes->getNamedItem('href')->value;
				if(strpos($link, 'mailto:') !== false) {
					$link = str_replace('mailto:', '', $link);
					if(filter_var($link, FILTER_VALIDATE_EMAIL)) {
						$links_array[] =  $link;
					}
				}
			}
		}
		$links_array = array_unique($links_array);
		return $links_array;
	}
	
	public function getEmailsFromSource() {
		$links = $this->source;
		$matches_details = array();
		$matches_unique = array();
		preg_match_all('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b/i', $links, $matches_details);
		foreach($matches_details as $matches) {
			foreach($matches as $match) {
				$matches_unique[] = $match;
			}
		}
		$matches_unique = array_unique($matches_unique);
		return $matches_unique;
	}

    protected function getXPath($item) {
        $xPathDom = new DomDocument();
        @$xPathDom->loadHTML($item);	
        $xPath = new DOMXPath($xPathDom);
        return $xPath;
    }
	
}

