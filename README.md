# ted-translator-tracking

Tracking site to track translators' activities on Amara.org in the TED Open Translation Project

The TED Open Translation Project is a non-profit activity, where voluntary (mostly) non-professional translators translate videos publised on TED.com. The translation is done on Amara.org. The project is coordinated in every country by a volunteer translation coordinators, with the approval of TED. 

While Amara.org provides a great interface for translation, it is very poor on giving an overall view for translation coordinators about what is going on. So this project aimed to create a dashboard to monitor the entire translation pipeline (who is working on what, which translation is in which stage) plus gives basic up-to-date statistics.

This project consists of the following main parts:
* Back end: 
  * php scripts to scrape translator data (such as name, picture, user id, etc.) from TED.com
  * php scripts to gather translation and video related data from Amara.org via its API
  * sql database to store the data
  * shell scripts to run from cron for backup and housekeeping
* Front end:
  * php based pages to present the various data structures and reports
  * access is restricted to the select group of translation coordinators, authentication is done via the Facebook API
  
 With the current setup this site only gathers data about Hungarian translations, however it can be easily adjusted to any other languages. Note however, the API calls might cause a considerable load to Amara.
 
 FIXMEs / TO-DOs (as of April 2017):
 * The biggest issue is that when Amara removes a video from their DB, they do not announce it via their API. If they have deleted a video which we have referenced and stored in our DB, it will lead to broken querries, which are not properly addressed with this design and can overload the Amara API.
 * Amara has recently changed the API structure, that change needs to be updated here
 * Facebook authorization seems to be broken, perhaps they have issued an updated API.  
  
