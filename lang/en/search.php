<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for component 'search', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package   core
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['advancedsearch'] = 'Advanced search';
$string['all'] = 'All';
$string['author'] = 'Author';
$string['authorname'] = 'Author name';
$string['back'] = 'Back';
$string['beadmin'] = 'You need to be an admin user to use this page.';
$string['commenton'] = 'Comment on';
$string['createanindex'] = 'create an index';
$string['createdon'] = 'Created on';
$string['database'] = 'Database';
$string['databasestate'] = 'Indexing database state';
$string['datadirectory'] = 'Data directory';
$string['deletionsinindex'] = 'Deletions in index';
$string['doctype'] = 'Doctype';
$string['documents'] = 'documents';
$string['documentsfor'] = 'Documents for';
$string['documentsindatabase'] = 'Documents in database';
$string['documentsinindex'] = 'Documents in index';
$string['duration'] = 'Duration';
$string['emptydatabaseerror'] = 'Database table is not present, or contains no index records.';
$string['enteryoursearchquery'] = 'Enter your search query';
$string['errors'] = 'Errors';
$string['filesinindexdirectory'] = 'Files in index directory';
$string['globalsearchdisabled'] = 'Global searching is not enabled.';
$string['checkdb'] = 'Check database';
$string['checkdbadvice'] = 'Check your database for any problems.';
$string['checkdir'] = 'Check dir';
$string['checkdiradvice'] = 'Ensure the data directory exists and is writable.';
$string['invalidindexerror'] = 'Index directory either contains an invalid index, or nothing at all.';
$string['ittook'] = 'It took';
$string['next'] = 'Next';
$string['noindexmessage'] = 'Admin: There appears to be no search index. Please';
$string['normalsearch'] = 'Normal search';
$string['openedon'] = 'opened on';
$string['resultsreturnedfor'] = 'results returned for';
$string['runindexer'] = 'Run indexer (real)';
$string['runindexertest'] = 'Run indexer test';
$string['score'] = 'Score';
$string['search'] = 'Search';
$string['searching'] = 'Searching in ...';
$string['searchnotpermitted'] = 'You are not allowed to do a search';
$string['seconds'] = 'seconds';
$string['solutions'] = 'Solutions';
$string['statistics'] = 'Statistics';
$string['thesewordshelpimproverank'] = 'These words help improve rank';
$string['thesewordsmustappear'] = 'These words must appear';
$string['thesewordsmustnotappear'] = 'These words must not appear';
$string['title'] = 'Title';
$string['tofetchtheseresults'] = 'to fetch these results';
$string['totalsize'] = 'Total size';
$string['type'] = 'Type';
$string['uncompleteindexingerror'] = 'Indexing was not successfully completed, please restart it.';
$string['versiontoolow'] = 'Sorry, global search requires PHP 5.0.0 or later';
$string['whichmodulestosearch?'] = 'Which modules to search?';
$string['wordsintitle'] = 'Words in title';

/**
 * Strings for Global Search
 * To be merged later
*/

$string['advancequeries'] = 'More Advance Queries';
$string['authorfilterquery'] = 'From records having this author';
$string['delete'] = 'Delete';
$string['emptyqueryfield'] = 'Please enter a query to search';
$string['filterquery'] = 'Enter Filter Queries below. Insert a comma between multiple values.';
$string['filterqueryheader'] = 'Filter Query';
$string['globalsearch'] = 'Global Search';
$string['globalsearch_help'] = 'Features you can use while performing search queries. Search queries are contained within []:

* Fields: You can specify which fields you want results from.
[title:("moodle" + "perth")]: returns all records that contains both "moodle" and "perth" in the title.
Available fields: title, name, content, user, author.
* Boolean Operators ("AND", "OR", "NOT"): <br>[("moodle" AND "perth") OR ("moodle" AND "australia")]
* Wildcards ("&#42;", "?"): <br>["mo??dl&#42;"] returns both "moodle" and "moodledata".
* Proximity Searches ("~"): ["mood"~2] returns "moodle". <br>(2 alphabets away from "mood").<br>
["moodle australia"~3] returns results containing "moodle hq at perth australia" (the queried terms were within 3 words of each other)
* Boosting Terms ("^"): To boost certain words/phrases. <br>
["perth australia"^5 "australia"] will make results with the phrase "perth australia" more relevant.
';
$string['index'] = 'Index';
$string['modulefilterquery'] = 'From records belonging to this module';
$string['query'] = 'Enter Query';
$string['optimize'] = 'Optimize';
$string['titlefilterquery'] = 'From records having this title';
$string['filtertimesection'] = 'Limit search by time';
$string['reindex'] = 'Recreate search engine index';
$string['searchfromtime'] = 'Include search results modified after';
$string['searchtilltime'] = 'Include search results modified before';
