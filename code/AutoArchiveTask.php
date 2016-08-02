<?php

class AutoArchiveTask extends BuildTask
{
	public function getDescription()
	{
		return 'Moves expired pages to archive.';
	}
	
	public function getTitle()
	{
		return 'Auto Archive Task';
	}
	
	public function run($request)
	{
		$nl = Director::is_cli() ? "\n" : '<br />';
		if (self::ExpiredDataObjects()->exists())
		{
			echo sprintf("Found %s expired pages.$nl",self::ExpiredDataObjects()->count());
			/** @var DataObject|AutoArchivableExtension $data_object */
			foreach (self::ExpiredDataObjects() as $data_object)
			{
				echo sprintf("Archiving %s #%s.$nl", $data_object->ClassName, $data_object->ID);
				$data_object->Archive();
			}
		}
		else
		{
			echo "No expired pages found.$nl";
		}
		echo "FINISHED!$nl";
	}
	
	/**
	 * @return ArrayList
	 */
	private static function ExpiredDataObjects()
	{
		$data_objects = new ArrayList();
		foreach (AutoArchivableExtension::getExtendedClasses() as $class_name)
		{
			$data_objects->merge(DataObject::get($class_name)->filter(array(
				'AutoArchiveOn'				=> true,
				'AutoArchiveDate:LessThanOrEqual'	=> date('Y-m-d'),
			)));
		}
		return $data_objects;
	}
}