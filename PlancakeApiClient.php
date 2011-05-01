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

class PlancakeApiClient
{
    /**
     *
     * @var string
     */
    private $apiKey = '';

    /**
     *
     * @var string
     */
    private $apiSecret = '';

    /**
     *
     * @var string
     */
    private $apiEndpointUrl = '';

    /**
     * Used rarely, not applicable to personal API keys
     *
     * @var string
     */
    private $emailAddress = null;

    /**
     * Used rarely, not applicable to personal API keys
     *
     * @var string
     */
    private $password = null;

    /**
     * @var string
     */
    public $token = '';

    /**
     * @var string
     */
    public $extraInfoForGetTokenCall = '';

    const API_VER = 3;

    const INVALID_TOKEN_ERROR = 30;

    /**
     *
     * @param string $apiKey - i.e.: rei93454jherER5439utkerj43534ter
     * @param string $apiSecret - i.e.: t4r95FDS4Erjt3lk
     * @param string $apiEndpointUrl - i.e.: http://www.plancake/api.php
     * @param string $emailAddress (=null) - used rarely, not applicable to personal API keys
     * @param string $password (=null) - used rarely, not applicable to personal API keys
     */
    public function  __construct($apiKey, $apiSecret, $apiEndpointUrl, $emailAddress=null, $password=null)
    {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->apiEndpointUrl = $apiEndpointUrl;

        if ($emailAddress !== null)
        {
            $this->emailAddress = $emailAddress;
        }

        if ($password !== null)
        {
            $this->password = $password;
        }
    }

    /**
     *
     * @param array $params - including token
     * @param string $methodName
     * @return string
     */
    private function getSignatureForRequest(array $params, $methodName)
    {
        ksort($params);

        $str = $methodName;
        foreach($params as $k => $v)
        {
            $str .= $k . $v;
        }

        $str .= $this->apiSecret;

        return md5($str);
    }

    /**
     *
     * @param array $params
     * @param string $methodName
     * @return string
     */
    private function prepareRequest(array $params, $methodName)
    {
        $params['token'] = $this->token;
        $params['api_ver'] = self::API_VER;

        $signature = $this->getSignatureForRequest($params, $methodName);

        $request = $this->apiEndpointUrl . '/' . $methodName . '/?';

        foreach($params as $k => $v)
        {
            $request .= $k . '=' . urlencode($v) . '&';
        }

        $request .= 'sig=' . $signature;

        return $request;
    }

    private function resetToken()
    {
        // we don't have a token yet or it has been reset as
        // it was probably expired

        $this->token = '';

        $params = array('token' => '',
                        'api_key' => $this->apiKey,
                        'api_ver' => self::API_VER);

        if (strlen($this->extraInfoForGetTokenCall) > 0)
        {
            $params['extra_info'] = $this->extraInfoForGetTokenCall;
        }

	if ($this->emailAddress !== null)
	{
	   $params['user_email'] = $this->emailAddress;		  
	}

	if ($this->password != null)
	{
	   $params['user_pwd'] = $this->password;		  
	}

	if ($this->userKey != null)
	{
	   $params['user_key'] = $this->userKey;
	}

        $request = $this->prepareRequest($params, 'getToken');

        $response = json_decode(file_get_contents($request));

        if (isset($response->error))
        {
           throw new Exception("Error " . $response->error);
        }

        $this->token = $response->token;
    }

    /**
     *
     * @param array $params
     * @param string $methodName
     * @param array $requestOpts (= null) - the options for the HTTP request
     * @return stdClass - result of json_decode
     */
    private function sendRequest(array $params, $methodName, array $requestOpts = null)
    {
        if (!strlen($this->token))
        {
            $this->resetToken();
        }

        if ($requestOpts === null)
        {
            $requestOpts = array('http' =>
                array(
                    'method'  => 'GET'
                )
            );
        }

        // enforcing the charset for the request
        $requestOpts['http']['header'] = "Content-Type: text/plain; charset=utf-8";

        $requestContext = stream_context_create($requestOpts);

        $request = $this->prepareRequest($params, $methodName);

        $response = json_decode(file_get_contents($request, false, $requestContext), true);

        // checking whether an error occurred
        if (isset($response->error))
        {
             // if the error is an INVALID_TOKEN_ERROR, we try to get the token again
             // (maybe it was just expired)
            if ($response->error == self::INVALID_TOKEN_ERROR)
            {
                $this->resetToken();

                $request = $this->prepareRequest($params, $methodName);

                $response = json_decode(file_get_contents($request, false, $requestContext), true);

                if (isset($response->error))
                {
                   throw new Exception("Error " . $response->error);
                }
            }
            else
            {
                throw new Exception("Error " . $response->error);
            }
        }

        return $response;
    }

    /**
     *
     * @return array
     */
    public function getServerTime()
    {
        $params = array();
        $methodName = __FUNCTION__;

        $response = $this->sendRequest($params, $methodName);
        return $response;
    }

     /**
     *
     * @return array
     */
    public function getUserSettings()
    {
        $params = array();
        $methodName = __FUNCTION__;

        $response = $this->sendRequest($params, $methodName);

        return $response;
    }

     /**
     * @param int $fromTimestamp (=null) - to return only the lists created or edited after this timestamp (GMT)
     * @param int $toTimestamp (=null) - to return only the lists created or edited till this timestamp (GMT)
     * @return array
     */
    public function getLists($fromTimestamp = null, $toTimestamp = null)
    {
        $params = array();

        if ($fromTimestamp !== null)
        {
            $params['from_ts'] = $fromTimestamp;
            $params['to_ts'] = $toTimestamp;
        }

        $methodName = __FUNCTION__;

        $response = $this->sendRequest($params, $methodName);

        return $response;
    }

     /**
     * @param int $fromTimestamp  - to return only the lists deleted after this timestamp (GMT)
     * @param int $toTimestamp  - to return only the lists deleted till this timestamp (GMT)
     * @return array
     */
    public function getDeletedLists($fromTimestamp, $toTimestamp)
    {
        $params['from_ts'] = $fromTimestamp;
        $params['to_ts'] = $toTimestamp;

        $methodName = __FUNCTION__;

        $response = $this->sendRequest($params, $methodName);

        return $response;
    }

     /**
     * @param int $fromTimestamp (=null) - to return only the tags created or edited after this timestamp (GMT)
     * @param int $toTimestamp (=null) - to return only the tags created or edited till this timestamp (GMT)
     * @return array
     */
    public function getTags($fromTimestamp = null, $toTimestamp = null)
    {
        $params = array();

        if ($fromTimestamp !== null)
        {
            $params['from_ts'] = $fromTimestamp;
            $params['to_ts'] = $toTimestamp;
        }

        $methodName = __FUNCTION__;

        $response = $this->sendRequest($params, $methodName);

        return $response;
    }

     /**
     * @param int $fromTimestamp - to return only the tags deleted after this timestamp (GMT)
     * @param int $toTimestamp - to return only the tags deleted till this timestamp (GMT)
     * @return array
     */
    public function getDeletedTags($fromTimestamp, $toTimestamp)
    {
        $params['from_ts'] = $fromTimestamp;
        $params['to_ts'] = $toTimestamp;

        $methodName = __FUNCTION__;

        $response = $this->sendRequest($params, $methodName);

        return $response;
    }

     /**
     * @param int $fromTimestamp (=null) - to return only the entries created or edited after this timestamp (GMT)
     * @param int $toTimestamp (=null) - to return only the entries created or edited till this timestamp (GMT)
     * @return array
     */
    public function getRepetitionOptions($fromTimestamp = null, $toTimestamp = null)
    {
        $params = array();

        if ($fromTimestamp !== null)
        {
            $params['from_ts'] = $fromTimestamp;
            $params['to_ts'] = $toTimestamp;
        }

        $methodName = __FUNCTION__;

        $response = $this->sendRequest($params, $methodName);

        return $response;
    }

    /**
     *
     * @param int $fromTimestamp (=null) - to return only the tasks created or edited after this timestamp (GMT)
     * @param int $toTimestamp (=null) - to return only the tasks created or edited till this timestamp (GMT)
     * @param int $taskId (=null)
     * @param int $listId (=null)
     * @param int $tagId (=null)
     * @param bool $completed (=false)
     * @param bool $onlyWithDueDate (=false)
     * @param bool $onlyWithoutDueDate (=false)
     * @param bool $onlyDueTodayOrTomorrow (=false)
     * @param bool $onlyStarred (=false)
     * @return array
     */
    public function getTasks($fromTimestamp = null,
                             $toTimestamp = null,
                             $taskId = null,
                             $listId = null,
                             $tagId = null,
                             $completed = false,
                             $onlyWithDueDate = false,
                             $onlyWithoutDueDate = false,
                             $onlyDueTodayOrTomorrow = false,
                             $onlyStarred = false)
    {
        if ($fromTimestamp !== null)
        {
            $params['from_ts'] = $fromTimestamp;
            $params['to_ts'] = $toTimestamp;
        }
        if ($taskId !== null)
            $params['task_id'] = $taskId;
        if ($listId !== null)
            $params['list_id'] = $listId;
        if ($tagId !== null)
            $params['tag_id'] = $tagId;

        $params['completed'] = $completed;
        $params['only_with_due_date'] = $onlyWithDueDate;
        $params['only_without_due_date'] = $onlyWithoutDueDate;
        $params['only_due_today_or_tomorrow'] = $onlyDueTodayOrTomorrow;
        $params['only_starred'] = $onlyStarred;

        $methodName = __FUNCTION__;

        $response = $this->sendRequest($params, $methodName);

        return $response;
    }

     /**
     * @param int $fromTimestamp - to return only the tasks deleted after this timestamp (GMT)
     * @param int $toTimestamp- to return only the tasks deleted till this timestamp (GMT)
     * @return array
     */
    public function getDeletedTasks($fromTimestamp, $toTimestamp)
    {
        $params['from_ts'] = $fromTimestamp;
        $params['to_ts'] = $toTimestamp;

        $methodName = __FUNCTION__;

        $response = $this->sendRequest($params, $methodName);

        return $response;
    }

    /**
     *
     * @param int $taskId
     * @param string $baselineDueDate (='') - in the format YYYY-mm-dd
     *        if used, this is to make sure a task is not completed twice but different applications
     * @return array
     */
    public function completeTask($taskId, $baselineDueDate = '')
    {
        $params['task_id'] = $taskId;

        if (strlen($baselineDueDate))
        {
            $params['baseline_due_date'] = $baselineDueDate;
        }

        $methodName = __FUNCTION__;

        $response = $this->sendRequest($params, $methodName);

        return $response;
    }

    /**
     *
     * @param int $taskId
     * @return array
     */
    public function uncompleteTask($taskId)
    {
        $params['task_id'] = $taskId;

        $methodName = __FUNCTION__;

        $response = $this->sendRequest($params, $methodName);

        return $response;
    }


    /**
     *
     * @param int $taskId
     * @param string $note
     * @return array
     */
    public function setTaskNote($taskId, $note)
    {
        $params['task_id'] = $taskId;
        $params['note'] = $note;

        $methodName = __FUNCTION__;

        // by specification, the note is sent with the POST method
        $requestOpts = array('http' =>
            array(
                'method'  => 'POST',
                'note' => $params['note']
            )
        );

        $response = $this->sendRequest($params, $methodName, $requestOpts);

        return $response;
    }

    /**
     *
     * @param string $description
     * @param int $listId (=null)
     * @param bool $isStarred (=false)
     * @param bool $isHeader (=false)
     * @param string $dueDate (=null) // in the yyyy-mm-dd format
     * @param string $dueTime (=null) // in the HH:mm 24h format (i.e.: 09:15, 19:13)
     * @param int $repetitionId (=null)
     * @param int $repetitionParam (=null)
     * @param string $note (='')
     * @param string $tagIds (=null) - comma-separated
     * @return array
     */
    public function addTask($description, $listId = null, $isStarred = false, $isHeader = false,
                            $dueDate = null, $dueTime = null,
                            $repetitionId = null, $repetitionParam = null, $repetitionIcalRrule = null,
                            $note = '', $tagIds = null)
    {
        $params['descr'] = $description;
        $params['is_header'] = $isHeader;
        $params['is_starred'] = $isStarred;

        if ($listId !== null)
            $params['list_id'] = $listId;
        if ($dueDate !== null)
            $params['due_date'] = $dueDate;
        if ($dueTime !== null)
            $params['due_time'] = $dueTime;
        if ($repetitionId !== null)
            $params['repetition_id'] = $repetitionId;
        if ($repetitionParam !== null)
            $params['repetition_param'] = $repetitionParam;
        if ($repetitionIcalRrule !== null)
            $params['repetition_ical_rrule'] = $repetitionIcalRrule;
        if ($note !== null)
            $params['note'] = $note;
        if ($tagIds !== null)
            $params['tag_ids'] = $tagIds;

        $methodName = __FUNCTION__;

        $response = $this->sendRequest($params, $methodName);

        return $response;
    }

    /**
     *
     * @param int $taskId
     * @param string $description (=null)
     * @param int $listId (=null)
     * @param bool $isStarred (=false)
     * @param bool $isHeader (=false)
     * @param string $dueDate (=null) // in the yyyy-mm-dd format
     * @param string $dueTime (=null) // in the HH:mm 24h format (i.e.: 09:15, 19:13)
     * @param int $repetitionId (=null)
     * @param int $repetitionParam (=null)
     * @param string $note (='')
     * @param string $tagIds (=null) - comma-separated
     * @return array
     */
    public function editTask($taskId, $description = null, $listId = null, $isStarred = null, $isHeader = null,
                            $dueDate = null, $dueTime = null,
                            $repetitionId = null, $repetitionParam = null, $repetitionIcalRrule = null,
                            $note = null, $tagIds = null)
    {
        $params['task_id'] = $taskId;

        if ($description !== null)
            $params['descr'] = $description;   
        if ($listId !== null)
            $params['list_id'] = $listId;
        if ($dueDate !== null)
            $params['due_date'] = $dueDate;
        if ($dueTime !== null)
            $params['due_time'] = $dueTime;
        if ($repetitionId !== null)
            $params['repetition_id'] = $repetitionId;
        if ($repetitionParam !== null)
            $params['repetition_param'] = $repetitionParam;
        if ($repetitionIcalRrule !== null)
            $params['repetition_ical_rrule'] = $repetitionIcalRrule;
        if ($note !== null)
            $params['note'] = $note;
        if ($tagIds !== null)
            $params['tag_ids'] = $tagIds;
        if ($isHeader !== null)
            $params['is_header'] = $isHeader;
        if ($isStarred !== null)
            $params['is_starred'] = $isStarred;

        $methodName = __FUNCTION__;

        $response = $this->sendRequest($params, $methodName);

        return $response;
    }

    /**
     *
     * @param int $taskId
     * @return array
     */
    public function deleteTask($taskId)
    {
        $params['task_id'] = $taskId;

        $methodName = __FUNCTION__;

        $response = $this->sendRequest($params, $methodName);

        return $response;
    }

    /**
     *
     * @param int $fromTimestamp - to return only the tags deleted after this timestamp (GMT)
     * @param int $toTimestamp - to return only the tags deleted till this timestamp (GMT)
     * @return array
     */
    public function whatHasChanged($fromTimestamp, $toTimestamp)
    {
        $params['from_ts'] = $fromTimestamp;
        $params['to_ts'] = $toTimestamp;

        $methodName = __FUNCTION__;

        $response = $this->sendRequest($params, $methodName);

        return $response;
    }
}
