autoarchivable
==============

This module provides a way to define dates when certain pages should be automatically archived. By archiving I mean to move a page to another location in the website and still keep it published. 

The module doesn't lock you to only use some specific class or inherit from a class defined by the module. Instead, the module provides DataExtensions that you will use to decorate your own classes, be them something like Page or SiteTree. Or better yet, create new classes like ArchivablePage and ArchiveDestination that both extend Page or SiteTree. But in the end: you decide. The only limitation is that your classes must be extend SiteTree - the module won't work on any other DataObjects! (If somebody would like to remove this limitation, I would gladly review a pull request about it! :) ).

Main features
-------------
- Give a page a date when it should be moved to an archive page
- On/off checkbox so that you can temporarily disable archiving for a specific page
- Dropdown list to select a destination for a certain page
- Or instead of using the above, you can use auto mode to decide where a bunch of pages should be archived, even if you have multiple destinations.
- "Archive now" checkbox to do it permanently for a specific page without playing with drag-and-drop-to-wrong-place-and-try-again.
- Automatize the module using for example cron, [silverstripe-labs/silverstripe-crontask](https://github.com/silverstripe-labs/silverstripe-crontask), URL or a custom cli script.


Getting hands dirty
-------------------

First you just need a few new SiteTree classes:

```PHP
class ArchivablePage extends Page
{
	private static $extensions = array('AutoArchivableExtension');
}

class ArchivablePage_Controller extends Page_Controller
{
}

class ArchiveDestination extends Page
{
	private static $extensions = array('AutoArchiveDestination');
}

class ArchiveDestination_Controller extends Page_Controller
{
}
```

Let's see what we have here... ArchivablePage is a class for pages that can be moved to somewhere automatically. The reason why to have an own class for these pages is that perhaps you do not want ALL pages to have the auto archive options cluttering the CMS, you just need them for some pages. There not much more about the class. You can of course put the extension to some your already existing class and of course it's possible to define the extension in YAML too. This was just a tidy way to show the setup.

Then we need to have a place where to actually move stuff when we will finally come to that point. This is what ArchiveDestination is used for. The system is simple and flexible, but hopefully not complicated. The simplest site would use one page of this class and place it where ever needed in the page tree. The module finds it and moves the archivable pages under it when it's time to do so.

If you need multiple destinations, that can be done too. Some pages will go here and some pages there. First of all, you have an option to select to which destination page a certain archivable page will go, which is good for precise control.

But if you have a lot of archivable pages, you don't want to define the destinations manually for every one of them. In this case, you can leave the destination option to it's default value, auto mode. Auto mode will take a look at the archivable page's siblings and see if one of them is a destination page. If is, it will be moved there. If not, it will look at the archivable page's parent's siblings, grand parent's siblings etc. If it finds nothing when traversing up the page tree, it will finally try to find a destination page regardless of it's position. If it finds multiple, it will use the first one. Simple. Perhaps not perfect for all cases, but if you have ideas, please share! :)

And finally there are cases when an archivable page cannot be moved:
- No destination page was found
- A destination page was found, but the page does not accept the archivable page to be it's new child.


Roadmap & stability
-------------------

The current status of this module is that it's still in development. I think that the features are in place and there's nothing on my mind right now that would need to be added to this module. But the features may be buggy, as the module is not very well tested. If testing goes well, I can easily rise the version number to 1.0.0 and release a stable version.


The final piece of code
-----------------------

The module is not automatically automatic, you will first need to do something manually. For example, put this to your crontab:

```
0 0 * * * php framework/cli-script.php dev/tasks/AutoArchiveTask
```

This will make your server to perform the archiving every midnight.

Or to test it manually, just visit: www.yoursite.com/dev/tasks/AutoArchiveTask


The author & license
--------------------

Name: Jarkko Linnanvirta
Company: IT-Palvelu Taitava
Contact: posti taitavasti fi (replace spaces with an at and a dot)
Webpage: http://taitavasti.fi (Too bad, only in Finnish :D)
Licence: MIT
Perhaps available for small freelance projects using SilverStripe :)

Please feel free to contact if you have questions.