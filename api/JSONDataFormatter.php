<?php

class JSONDataFormatter extends DataFormatter {
	/**
	 * @todo pass this from the API to the data formatter somehow
	 */
	static $api_base = "api/v1/";
	
	public function supportedExtensions() {
		return array('json', 'js');
	}
	
	/**
	 * Generate an XML representation of the given {@link DataObject}.
	 * 
	 * @param DataObject $obj
	 * @param $includeHeader Include <?xml ...?> header (Default: true)
	 * @return String XML
	 */
	public function convertDataObject(DataObjectInterface $obj) {
		$className = $obj->class;
		$id = $obj->ID;
		
		$json = "{\n  className : \"$className\",\n";
		$dbFields = array_merge($obj->inheritedDatabaseFields(), array('ID'=>'Int'));
		foreach($dbFields as $fieldName => $fieldType) {
			if(is_object($obj->$fieldName)) {
				$jsonParts[] = "$fieldName : " . $obj->$fieldName->toJSON();
			} else {
				$jsonParts[] = "$fieldName : \"" . Convert::raw2js($obj->$fieldName) . "\"";
			}
		}

		if($this->relationDepth > 0) {
			foreach($obj->has_one() as $relName => $relClass) {
				$fieldName = $relName . 'ID';
				if($obj->$fieldName) {
					$href = Director::absoluteURL(self::$api_base . "$relClass/" . $obj->$fieldName);
				} else {
					$href = Director::absoluteURL(self::$api_base . "$className/$id/$relName");
				}
				$jsonParts[] = "$relName : { className : \"$relClass\", href : \"$href.json\", id : \"{$obj->$fieldName}\" }";
			}
	
			foreach($obj->has_many() as $relName => $relClass) {
				$jsonInnerParts = array();
				$items = $obj->$relName();
				foreach($items as $item) {
					//$href = Director::absoluteURL(self::$api_base . "$className/$id/$relName/$item->ID");
					$href = Director::absoluteURL(self::$api_base . "$relClass/$item->ID");
					$jsonInnerParts[] = "{ className : \"$relClass\", href : \"$href.json\", id : \"{$obj->$fieldName}\" }";
				}
				$jsonParts[] = "$relName : [\n    " . implode(",\n    ", $jsonInnerParts) . "  \n  ]";
			}
	
			foreach($obj->many_many() as $relName => $relClass) {
				$jsonInnerParts = array();
				$items = $obj->$relName();
				foreach($items as $item) {
					//$href = Director::absoluteURL(self::$api_base . "$className/$id/$relName/$item->ID");
					$href = Director::absoluteURL(self::$api_base . "$relClass/$item->ID");
					$jsonInnerParts[] = "    { className : \"$relClass\", href : \"$href.json\", id : \"{$obj->$fieldName}\" }";
				}
				$jsonParts[] = "$relName : [\n    " . implode(",\n    ", $jsonInnerParts) . "\n  ]";
			}
		}
		
		return "{\n  " . implode(",\n  ", $jsonParts) . "\n}";	}

	/**
	 * Generate an XML representation of the given {@link DataObjectSet}.
	 * 
	 * @param DataObjectSet $set
	 * @return String XML
	 */
	public function convertDataObjectSet(DataObjectSet $set) {
		$jsonParts = array();
		foreach($set as $item) {
			if($item->canView()) $jsonParts[] = $this->convertDataObject($item);
		}
		return "[\n" . implode(",\n", $jsonParts) . "\n]";
	}
}