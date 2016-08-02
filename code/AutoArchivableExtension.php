<?php

/**
 * Class AutoArchivableExtension
 *
 * @method SiteTree AutoArchiveDestination()
 *
 * @property SiteTree|AutoArchivableExtension $owner
 * @property boolean $AutoArchiveOn
 * @property string $AutoArchiveDate
 */
class AutoArchivableExtension extends DataExtension
{
	private static $db = array(
		'AutoArchiveOn'		=> 'Boolean',
		'AutoArchiveDate'	=> 'Date',
	);

	private static $has_one = array(
		'AutoArchiveDestination'=> 'SiteTree',
	);
	
	public function updateSummaryFields(&$fields)
	{
		$fields = $fields + array(
			'AutoArchiveOn'		=> 'AutoArchiveOn',
			'AutoArchiveDate'	=> 'AutoArchiveDate',
		);
	}
	
	public function updateCMSFields(FieldList $fields)
	{
		$fields->addFieldToTab('Root', new Tab('AutoArchive', _t('AutoArchivableExtension.AutoArchiveTab', 'Archiving')));
		$fields->addFieldsToTab('Root.AutoArchive', array(
			new CheckboxField('AutoArchiveOn', _t('AutoArchivableExtension.AutoArchiveOn', 'Archive this page automatically')),
			new DateField('AutoArchiveDate', _t('AutoArchivableExtension.AutoArchiveDate', 'When to archive')),
			new DropdownField('AutoArchiveDestinationID', _t('AutoArchivableExtension.AutoArchiveDestination', 'Archive to'), array(0=>_t('AutoArchivableExtension.AutoArchiveAutomaticDestination', '(Decide a destination automatically)'))+$this->AllDestinations()->map()),
			new CheckboxField('AutoArchiveNow', _t('AutoArchivableExtension.ArchiveNow', 'Archive now')),
		));
	}
	
	public function AutoArchiveOn()
	{
		$value = $this->owner->AutoArchiveOn ? 'Yes' : 'No';
		return _t("AutoArchiveExtension.$value", $value);
	}
	
	public function onBeforeWrite()
	{
		if ($this->owner->AutoArchiveNow && $this->owner->exists()) //AutoArchiveNow is not a database field! This comes from AutoArchivableExtension::updateCMSFields().
		{
			$this->owner->Archive();
		}
	}
	
	/**
	 * Finds a suitable destination page for this archivable page and moves it there.
	 *
	 * @return bool True, if a suitable destination was found. False otherwise.
	 */
	public function Archive()
	{
		if ($this->getDestination()) //Ensure that we have a destination, the RunArchiveQuery() method will then fetch it again
		{
			$this->RunArchiveQuery($this->owner->baseTable(), $this->owner->ClassName);
			if ($this->owner->hasExtension('Versioned'))
			{
				//Need to move the published version too
				$this->RunArchiveQuery($this->owner->baseTable().'_Live', $this->owner->ClassName.'_Live');
			}
			return true;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * TODO: Rewrite this. Should not use a custom query. It's now made this way because I do not know a better way to
	 * make these changes to both the staged and live versions without the CMS yelling that the moved page has unpublished
	 * changes even when it actually does not have. Still, this is not a good way to solve this. For example, this skips
	 * version history completely.
	 *
	 * @param $base_table
	 * @param $inherited_table
	 */
	private function RunArchiveQuery($base_table, $inherited_table)
	{
		DB::query(sprintf(' UPDATE `%s` SET ParentID = %s WHERE ID = %s LIMIT 1',
			$base_table, (int) $this->getDestination()->ID, (int) $this->owner->ID));
		DB::query(sprintf(' UPDATE `%s` SET AutoArchiveOn = false WHERE ID = %s LIMIT 1',
			$inherited_table, (int) $this->owner->ID));
	}
	
	/**
	 * Finds a page under which this page will be moved when archived.
	 *
	 * @return null|SiteTree
	 */
	public function getDestination()
	{
		if ($this->owner->AutoArchiveDestination()->exists())
		{
			//1. This page has a specific destination page defined
			return $this->owner->AutoArchiveDestination();
		}
		else
		{
			$destinations = $this->AllDestinations();
			if (!$destinations->exists()) return null; //No suitable destinations exist :(
			
			//2. Try to find a suitable destination from this page's siblings or parent's siblings, grand parent's siblings etc..
			$parents = $this->owner->parentStack();
			array_shift($parents);
			foreach ($parents as $parent)
			{
				$parent_id	= !$parent ? 0 : $parent->ID;
				$destination	= $destinations->filter('ParentID', $parent_id)->first();
				if ($destination) return $destination;
			}
			
			//3. Return just whatever suitable destination, no matter where is it located
			return $destinations->first();
		}
	}


	/**
	 * Returns a list of all SiteTree objects that are suitable to be parents for $this->owner when it gets archived.
	 *
	 * @return ArrayList
	 */
	private function AllDestinations()
	{
		$owner			= $this->owner;
		$destination_candidates = new ArrayList();
		foreach (AutoArchiveDestinationExtension::getExtendedClasses() as $class_name)
		{
			$destination_candidates->merge(DataObject::get($class_name)->filterByCallback(function (SiteTree $destination_candidate) use ($owner)
			{
				return in_array($owner->ClassName, $destination_candidate->allowedChildren());
			}));
		}
		return $destination_candidates;
	}

	/**
	 * Find out which classes are extended by this extension.
	 *
	 * Originally from: http://stackoverflow.com/a/26148610/2754026
	 *
	 * @return array Array of strings
	 */
	public static function getExtendedClasses()
	{
		$classes = array();
		foreach(ClassInfo::subclassesFor('Object') as $class_name)
		{
			if (Object::has_extension($class_name, __CLASS__))
			{
				$classes[] = $class_name;
			}
		}
		return $classes;
	}

}