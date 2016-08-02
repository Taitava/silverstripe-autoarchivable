<?php

class AutoArchiveDestinationExtension extends DataExtension
{
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

	public function updateCMSFields(FieldList $fields)
	{
		$gridfield_config = new GridFieldConfig_RecordViewer();
		$translation      = _t('AutoArchiveDestinationExtension.Archivables', 'Archivables');
		$fields->addFieldToTab('Root', new Tab('ArchivablesTab', $translation));
		$fields->addFieldToTab('Root.ArchivablesTab', $gridfield = new GridField('Archivables', $translation, $this->Archivables(true), $gridfield_config));
		$gridfield->setModelClass($this->Archivables()->exists() ? $this->Archivables()->first()->ClassName : AutoArchivableExtension::getExtendedClasses()[0]);
		$gridfield->setDescription(_t('AutoArchiveDestinationExtension.ArchivablesTabDescription','This list shows all pages that will be moved under this page when/if they will be archived in the future.'));
	}
	
	/**
	 * Returns all pages that will be moved under this page when they will be archived in the future. Does not return
	 * pages that are already located under this page.
	 *
	 * @param bool $include_disabled If true, also those pages will be returned that have AutoArchiveOn set to false.
	 * @return ArrayList
	 */
	public function Archivables($include_disabled = false)
	{
		$result	= new ArrayList();
		$owner	= $this->owner;
		foreach (AutoArchivableExtension::getExtendedClasses() as $class_name)
		{
			$archivables = SiteTree::get($class_name)->exclude('ParentID', $owner->ID);
			if (!$include_disabled) $archivables = $archivables->exclude('AutoArchiveOn', false);
			$result->merge($archivables->filterByCallback(function ($archivable) use ($owner)
			{
				if ($archivable->getDestination())
				{
					return $owner->ID == $archivable->getDestination()->ID;
				}
				else
				{
					return false;
				}
			}));
		}
		return $result;
	}
}