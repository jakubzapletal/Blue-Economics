Blue-Economics
==============


#### Development
1. Setup an Apache server on your local machine. [Windows](http://www.wampserver.com/en/), [OSX](http://www.mamp.info/en/), [Linux](https://www.linux.com/learn/tutorials/288158-easy-lamp-server-installation)
2. Clone the repo into the www/ folder: `git clone https://github.com/innls/Blue-Economics.git`
3. Create a new branch: `git checkout -b my_sweet_branch`
4. Once you have some working code that has been tested locally and ready to be put into production, push it back up and open a pull request: `git push origin my_sweet_branch`

## Files structure

* **app**
  * config
  * db _(schema.xml, etc.)_
    * fixtures _(data for loading into empty DB)_
* **public** _(all static data)_
  * css
  * js
  * img
  * media _(video and audio files)_
  * views
* **src** _(classes, DB Propel entities, etc... PHP FILES, all classes should have some project namespace, for example "BlueEconomics")_
  * BlueEconomics _(folder with namespaced content)_
    * Entities
    * Controllers _(if we use for example Silex)_
    * Tests _(unit or integration tests)_
    * ...
* **doc** _(everything what is needed for developing but is not directly used in responses or any PHP codes)_
  * mock_up _(graphic design templates)_
  * ... 
* **vendor** _(folders and files from Composer)_
* **bower_components** _(folders and files from Bower, if some needed)_
* **Tests** _(if we will create acceptance of functional tests)_
* .gitignore
* .htaccess
* composer.json
* composer.lock
* index.php
* README.md _(if readme.md would be too big, there will be links to partials documentation files stored in folder `doc` and will have also format .MD)_

## Database load

1. Update *Master.xlsx* with the latest data the BLS and other relevant sources
2. Save each tab to the respective txt file
3. Run `app/db/fixtures/jobs_data/data_loader.php --industry industries.txt --jobs national_jobs_data.txt --prospects nyc_bls_prospects_data.txt --wages nyc_bls_wage_data.txt`

Step 3 will load the data, compute custom BlueEconomics score and insert the data into the database.

In addition to this load, the script also loads the original dataset in `app/db/fixtures/DDL_TABDELIMITED_DATA/blueecondbDUMP.sql` into the databases