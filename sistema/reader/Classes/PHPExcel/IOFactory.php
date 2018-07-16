<?php

/**	PHPExcel root directory */
if (!defined('PHPEXCEL_ROOT')) {
	
	define('PHPEXCEL_ROOT', dirname(__FILE__) . '/../');
	require(PHPEXCEL_ROOT . 'PHPExcel/Autoloader.php');
}

class PHPExcel_IOFactory
{
	private static $_searchLocations = array(
	array('type' => 'IWriter',
		  'path' => 'PHPExcel/Writer/IWriter.php', 
		  'class' => 'PHPExcel_Writer_IWriter' ),
	array('type' => 'IReader',
		  'path' => 'PHPExcel/Reader/{0}.php', 
		  'class' => 'PHPExcel_Reader_{0}' )
	);
	
	

	private static $_autoResolveClasses = array(
		'Excel2007',
		'Excel5',
		'Excel2003XML',
		'OOCalc',
		'SYLK',
		'Gnumeric',
		'CSV',
	);


	public static function createReader($readerType) {
	// Search type
		$searchType = 'IReader';

		// Include class
		foreach (self::$_searchLocations as $searchLocation) {
			if ($searchLocation['type'] == $searchType) {
				$className = str_replace('{0}', $readerType, $searchLocation['class']);
				$classFile = str_replace('{0}', $readerType, $searchLocation['path']);

				$instance = new $className();
				if (!is_null($instance)) {
					return $instance;
				}
			}
		}

		// Nothing found...
		throw new Exception("No $searchType found for type $readerType");
	}	

	
	public static function createReaderForFile($pFilename) {
	
	
	
		// First, lucky guess by inspecting file extension
		$pathinfo = pathinfo($pFilename);

		if (isset($pathinfo['extension'])) {
			switch (strtolower($pathinfo['extension'])) {
				case 'xlsx':
					$reader = self::createReader('Excel2007');
					break;
				case 'xls':
					$reader = self::createReader('Excel5');
					break;
				case 'ods':
					$reader = self::createReader('OOCalc');
					break;
				case 'slk':
					$reader = self::createReader('SYLK');
					break;
				case 'xml':
					$reader = self::createReader('Excel2003XML');
					break;
				case 'gnumeric':
					$reader = self::createReader('Gnumeric');
					break;
				case 'csv':
					// Do nothing
					// We must not try to use CSV reader since it loads
					// all files including Excel files etc.
					break;
				default:
					break;
			}

			// Let's see if we are lucky
			if (isset($reader) && $reader->canRead($pFilename)) {
				return $reader;
			}

		}

		
	}	//	function createReaderForFile()
}
