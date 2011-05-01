<?php

/*************************************************************************************
* ===================================================================================*
* Software by: Danyuki Software Limited                                              *
* This file is part of Plancake.                                                     *
*                                                                                    *
* Copyright 2009-2010-2011 by:     Danyuki Software Limited                          *
* Support, News, Updates at:  http://www.plancake.com                                *
* Licensed under the AGPL version 3 license.                                         *                                                       *
* Danyuki Software Limited is registered in England and Wales (Company No. 07554549) *
**************************************************************************************
* Plancake is distributed in the hope that it will be useful,                        *
* but WITHOUT ANY WARRANTY; without even the implied warranty of                     *
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the                      *
* GNU Affero General Public License for more details.                                *
*                                                                                    *
* You should have received a copy of the GNU Affero General Public License           *
* along with this program.  If not, see <http://www.gnu.org/licenses/>.              *
*                                                                                    *
**************************************************************************************/

require_once('PlancakeApiClient.php');


try
{
    $plancakeApi = new PlancakeApiClient('91883a785e05fb087a76419dd826f8570a64288b', /* your API key here - check your Settings page */
                                         'o3zTZxE0Vds3zqiX',                 /* your API secret here - check your Settings page */
                                         'http://api.plancake.com/api.php'); /* the URL of the API endpoint */

    $plancakeApi->userKey = 'vah65fZKrwWecbtjh9pg8Za9iBxvXYJb';   /* Find your user key on your Settings page */



    
    $plancakeApi->extraInfoForGetTokenCall = "This is some info we can send to the server when requesting a token";

    // here is when we can "inject" a token we have cache from the previous requests
    // to avoid to compute it again (it would be just a waste!)
    // $plancakeApi->token = 'xxxxxxxxxx';

    $fromTimestamp = time()-(3600*24*2);
    $toTimestamp = $fromTimestamp + 10000;

    printTitle("Testing getServerTime");
    printResponse($plancakeApi->getServerTime());

    printTitle("Testing getUserSettings");
    printResponse($plancakeApi->getUserSettings());

    printTitle("Testing getLists");
    printResponse($plancakeApi->getLists());

    printTitle("Testing getLists (with timestamp)");
    printResponse($plancakeApi->getLists($fromTimestamp, $toTimestamp));

    printTitle("Testing getDeletedLists");
    printResponse($plancakeApi->getDeletedLists($fromTimestamp, $toTimestamp));

    printTitle("Testing getTags");
    printResponse($plancakeApi->getTags());

    printTitle("Testing getTags (with timestamp)");
    printResponse($plancakeApi->getTags($fromTimestamp, $toTimestamp));

    printTitle("Testing getDeletedTags");
    printResponse($plancakeApi->getDeletedTags($fromTimestamp, $toTimestamp));

    printTitle("Testing getRepetitionOptions");
    printResponse($plancakeApi->getRepetitionOptions());

    printTitle("Testing getTasks (non-completed)");
    printResponse($getTasksResponse = $plancakeApi->getTasks());

    $validTaskId = $getTasksResponse['tasks'][0]['id'];

    printTitle("TaskId to use later on: $validTaskId");    

    printTitle("Testing getTasks (completed)");
    printResponse($plancakeApi->getTasks(null, null, null, null, null, true, null, null, null));

    printTitle("Testing getTasks (various criteria)");
    printResponse($plancakeApi->getTasks($fromTimestamp, $toTimestamp, null, 2, 2, null, null, false, false));
    
    printTitle("Testing getDeletedTasks");
    printResponse($plancakeApi->getDeletedTasks($fromTimestamp, $toTimestamp));

    printTitle("Testing completeTask");
    // commented out to avoid unwanted effect
    //printResponse($plancakeApi->completeTask($validTaskId, '2011-03-04'));

    printTitle("Testing uncompleteTask");
    // commented out to avoid unwanted effect
    // printResponse($plancakeApi->uncompleteTask($validTaskId));

    printTitle("Testing setTaskNote");
    // commented out to avoid unwanted effect
    // printResponse($plancakeApi->setTaskNote($validTaskId, "this is a note from the API test"));

    printTitle("Testing addTask");
    printResponse($plancakeApi->addTask("this is a task from the API test"));

    printTitle("Testing addTask (misc parameters)");
    //printResponse($plancakeApi->addTask("this is a test", 2, false, false, '2010-11-18', '1930', null, null, null, 'this is a simple note', '1,2'));

    printTitle("Testing editTask");
    // commented out to avoid unwanted effect
    //printResponse($plancakeApi->editTask($validTaskId, "this is a test for the editTask API method"));

    printTitle("Testing editTask (misc parameters)");
    // commented out to avoid unwanted effect
    //printResponse($plancakeApi->editTask(22, "let's change this", 2, null, null, '2011-07-13', null, 10, 13, null, null, "1"));

    printTitle("Testing deleteTask");
    // commented out to avoid unwanted effect
    // printResponse($plancakeApi->deleteTask($validTaskId));

    printTitle("Testing whatHasChanged");
    printResponse($plancakeApi->whatHasChanged($fromTimestamp, $toTimestamp));

    printTitle("This is the token we have used and we can save for later use");
    echo $plancakeApi->token . "\n\n";
}
catch (Exception $e)
{
    die($e->getMessage() . "\n");
}




function printTitle($msg)
{
    echo "\n >>>>> $msg <<<<<\n";
}

function printResponse($response)
{
    echo print_r($response, true);
}
