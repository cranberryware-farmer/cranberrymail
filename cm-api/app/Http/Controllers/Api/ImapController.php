<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;
use Mail_mime;

class ImapController extends Controller
{
    private $oClient;
    private $user;

    /**
     * Create a new controller instance.
     *
     * @return void
     * @throws \Horde_Imap_Client_Exception
     */
    public function __construct(){
        $user = Auth::user();
        $this->user = $user;
        $email = $user->email;
        $password=Crypt::decryptString($user->key);

        $client = new \Horde_Imap_Client_Socket([
            'username' => $email,
            'password' => $password,
            'hostspec' => env('IMAP_HOST'),
            'port' => env('IMAP_PORT'),
            'secure' => env("IMAP_ENCRYPTION") //ssl,tls etc
        ]);
        Log::info("oClient created", ['file' => __FILE__, 'line' => __LINE__]);
        $client->login();
        Log::info("Login with oClient", ['file' => __FILE__, 'line' => __LINE__]);

        $this->oClient =  $client;
    }

    /**
     * @param string $folder_name
     * @return string
     */
    private function formatFolderName($folder_name) {
        $folder_arr = preg_split('/[.\/]+/', $folder_name);
        $folder_name = count($folder_arr) > 0 ? end($folder_arr) : $folder_name;
        return strtolower($folder_name);
    }

    /**
     * Fetch folders
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Horde_Imap_Client_Exception
     */

    public function get_folders(Request $request) {
        $oClient = $this->oClient;

        $mailBoxes = $this->getMailBoxes($oClient);
        Log::info("Got mailboxes", ['file' => __FILE__, 'line' => __LINE__]);
        $data = [];
        foreach($mailBoxes as $mailBox){
            $mailbox_name = $mailBox['mailbox']->utf8;
            $mailbox_formatted = $this->formatFolderName($mailbox_name);
            if(in_array($mailbox_formatted, ['drafts', 'draft'])){
                $request->session()->put('draft_folder', $mailbox_name);
            }
            array_push($data, $mailbox_name);
        }
        Log::info("Retrieved mailboxes as an array", ['file' => __FILE__, 'line' => __LINE__]);
        return response()->json($data, 200);
    }


    /**
     * Moves message to destination folder from source folder
     *
     * @param $oClient
     * @param $sourceFolder
     * @param $uid
     * @param $destinationFolder
     * @return Boolean
     */

    private function moveToFolder($oClient,$sourceFolder,$uid,$destinationFolder){

        $result = $oClient->copy($sourceFolder,$destinationFolder,[
            "ids" => $uid,
            "move" => true
        ]);

        if(!empty($result)){
            Log::info("Moved email from ".$sourceFolder." to folder ".$destinationFolder,['file' => __FILE__, 'line' => __LINE__]);
            return true;
        }
        Log::error("Unable to move email from ".$sourceFolder." to folder ".$destinationFolder,['file' => __FILE__, 'line' => __LINE__]);
        return false;
    }

    /**
     * Copy message to destination folder from source folder
     *
     * @param $oClient
     * @param $sourceFolder
     * @param $uid
     * @param $destinationFolder
     * @return Boolean
     */

    private function copyToFolder($oClient,$sourceFolder,$uid,$destinationFolder){

        $result = $oClient->copy($sourceFolder,$destinationFolder,[
            "create" => true,
            "ids" => $uid
        ]);

        if(!empty($result)){
            Log::info("Copied email from ".$sourceFolder." to folder ".$destinationFolder,['file' => __FILE__, 'line' => __LINE__]);
            return true;
        }
        Log::error("Unable to copy email from ".$sourceFolder." to folder ".$destinationFolder,['file' => __FILE__, 'line' => __LINE__]);
        return false;
    }

    /**
     * Move emails to inbox from trash.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Horde_Imap_Client_Exception
     */

    public function untrash_emails(Request $request) {
        $oClient = $this->oClient;

        $trash = $this->getMailBox($oClient,$request->input("trash"));
        $inbox = $this->getMailBox($oClient,$request->input("curfolder"));

        Log::info("Fetched trash and inbox",['file' => __FILE__, 'line' => __LINE__]);

        $uid = $request->input("uid");
        $uids = json_decode($uid,true);

        $ids = new \Horde_Imap_Client_Ids($uids);
        $result = $this->moveToFolder($oClient,$trash,$ids,$inbox);

        $data=[
            "result" => 1,
            "status" => $result
        ];

        Log::info("Data fetched in an array",['file' => __FILE__, 'line' => __LINE__]);

        return response()->json($data, 200);

    }

    /**
     * Move emails to trash.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Horde_Imap_Client_Exception
     * @throws \Horde_Imap_Client_Exception_NoSupportExtension
     */

    public function trash_emails(Request $request) {
        $oClient = $this->oClient;

        $trashFolder = $this->getMailBox($oClient,$request->input("trash"));
        $curFolder = $this->getMailBox($oClient,$request->input("curfolder"));

        Log::info("Fetched trash and curFolder",['file' => __FILE__, 'line' => __LINE__]);

        $uid = $request->input("uid");
        $uids = json_decode($uid,true);

        if($trashFolder->utf8 != $curFolder->utf8){

            Log::info("Not inside trash folder",['file' => __FILE__, 'line' => __LINE__]);

            $ids = new \Horde_Imap_Client_Ids($uids);
            $result = $this->moveToFolder($oClient,$curFolder,$ids,$trashFolder);
            $data=[
                "result" => 1,
                "status" => $result
            ];

        }else{
            Log::info("Inside folder other than trash",['file' => __FILE__, 'line' => __LINE__]);
            $oClient->store($trashFolder, array(
                    'ids' => new \Horde_Imap_Client_Ids($uids),
                    'add' => '\deleted',
            ));

            Log::info("Added deleted flag to given emails in trash folder",['file' => __FILE__, 'line' => __LINE__]);

            $result = $oClient->expunge($trashFolder,[
                'ids' => new \Horde_Imap_Client_Ids($uids),
                'list' => true
            ]);

            Log::info("Expunged given emails in trash folders",['file' => __FILE__, 'line' => __LINE__]);

            if(!empty($result)){
                Log::info("List of uid fetched after expunge",['file' => __FILE__, 'line' => __LINE__]);
                $result=1;
            }else{
                Log::info("No uids fetched after expunge",['file' => __FILE__, 'line' => __LINE__]);
                $result=0;
            }

            $data=[
                "result" => $result,
                "status" => boolval($result)
            ];
        }
        Log::info("@22: Returning data as an array",['file' => __FILE__, 'line' => __LINE__]);
        return response()->json($data, 200);


    }

    /**
     * Move emails to inbox from spam.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Horde_Imap_Client_Exception
     */

    public function unspam_emails(Request $request) {
        $oClient = $this->oClient;

        $spam = $this->getMailBox($oClient,$request->input("spam"));
        $inbox = $this->getMailBox($oClient,$request->input("curfolder"));

        Log::info("Got mailboxes for spam and inbox ",['file' => __FILE__, 'line' => __LINE__]);

        $uid = $request->input("uid");
        $uids = json_decode($uid,true);

        $ids = new \Horde_Imap_Client_Ids($uids);
        $result = $this->moveToFolder($oClient,$spam,$ids,$inbox);

        $data=[
            "result" => 1,
            "status" => $result
        ];

        Log::info("Returning the result of operation",['file' => __FILE__, 'line' => __LINE__]);

        return response()->json($data, 200);

    }

    /**
     * Move emails to inbox from spam.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Horde_Imap_Client_Exception
     */

    public function saveDraft(Request $request) {
        $oClient = $this->oClient;

        $draft_folder = $request->session()->get('draft_folder', '');

        $draft_id = $request->input("draft_id");

        if($draft_id) {
            $ids = new \Horde_Imap_Client_Ids($draft_id);

            $oClient->store($draft_folder, array(
                'ids' => $ids,
                'add' => '\deleted',
            ));

            Log::info("Added deleted flag to previous draft copy of the email",['file' => __FILE__, 'line' => __LINE__]);

            $result = $oClient->expunge($draft_folder,[
                'ids' => $ids,
                'list' => true
            ]);
        }

        $tos = $request->input("to");
        $ccs = $request->input("cc");
        $bccs = $request->input("bcc");
        $subject = $request->input("subject");
        $body=$request->input("body");

        $mail_mime = new Mail_mime();
        $mail_mime->addTo($tos);
        $mail_mime->addCc($ccs);
        $mail_mime->addBcc($bccs);
        $mail_mime->setSubject($subject);
        $mail_mime->setFrom($this->user->email);

        $mail_mime->setHTMLBody($body);

        if($request->hasFile('attachment')) {
            $files = $request->file('attachment');
            foreach($files as $file){
                $mail_mime->addAttachment($file, 'application/octet-stream', $file->getClientOriginalName());
            }
        } else if($request->input("attachmentURLs")) {
            $attached_urls = $request->input("attachmentURLs");
            $urls_arr = json_decode($attached_urls, true);
            foreach($urls_arr as $file){
                $file_path = storage_path('app/') . $file["file"];
                $mail_mime->addAttachment($file_path, 'application/octet-stream', $file["file"]);
            }
        }

        $msg = $mail_mime->getMessage();

        $append_body[] = ['data' => $msg];

        $draft = $oClient->append(
            $draft_folder,
            $append_body
        );
        $draft = $draft->ids;

        $data = [
            "success" => true,
            "draft" => $draft[0]
        ];

        Log::info("Returning the result of operation",['file' => __FILE__, 'line' => __LINE__]);

        return response()->json($data, 200);

    }

    /**
     * Mark emails as spam.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Horde_Imap_Client_Exception
     */

    public function spam_emails(Request $request) {
        $oClient = $this->oClient;

        $spamFolder = $this->getMailBox($oClient,$request->input("spam"));
        $curFolder = $this->getMailBox($oClient,$request->input("curfolder"));

        Log::info("Fetched spamfolder and currentfolder",['file' => __FILE__, 'line' => __LINE__]);

        $uid = $request->input("uid");
        $uids = json_decode($uid,true);

        if($spamFolder->utf8 != $curFolder->utf8){
            Log::info("Current folder is not spam folder",['file' => __FILE__, 'line' => __LINE__]);

            $ids = new \Horde_Imap_Client_Ids($uids);
            $result = $this->moveToFolder($oClient,$curFolder,$ids,$spamFolder);
            $data=[
                "result" => 1,
                "status" => $result
            ];

        }else{
            Log::info("Current folder is spam folder",['file' => __FILE__, 'line' => __LINE__]);
            $data=[
                "result" => 0,
                "status" => false
            ];
        }
        Log::info("Returning results of the operation",['file' => __FILE__, 'line' => __LINE__]);
        return response()->json($data, 200);
    }

    /**
     * Mark or Unmark emails as starred
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Horde_Imap_Client_Exception
     */

    public function star_emails(Request $request) {
        $oClient = $this->oClient;

        $curFolder = $request->input("curFolder");
        $starEmail = $request->input("emailState");



        $uid = $request->input("uid");
        $curFolder = $this->getMailBox($oClient, $request->input("curFolder"));
        $starred = $this->getMailBox($oClient, $request->input("starredFolder"));
        $inbox = $this->getMailBox($oClient, "inbox");

        Log::info("Fetched curFolder,starred and inbox",['file' => __FILE__, 'line' => __LINE__]);

        $uids = json_decode($uid,true);
        $result = true;
        $ids = new \Horde_Imap_Client_Ids($uids);


        if($starred==''){
            Log::info("Starred folder returned empty" , ['file' => __FILE__, 'line' => __LINE__]);
            $starred = $this->getMailBox($oClient, "INBOX.Starred");
            Log::info("Fetched starred folder again", ['file' => __FILE__, 'line' => __LINE__]);
        }

        if($starred==''){
            Log::info("Starred folder returned empty after 2nd trial", ['file' => __FILE__, 'line' => __LINE__]);
            $data = [
                "result" => 0,
                "status" => "Unable to create starred folder"
            ];
        }elseif($curFolder->utf8==$starred->utf8 && $starEmail == 1){
            Log::info("Inside starred folder and email already marked as star", ['file' => __FILE__, 'line' => __LINE__]);
            $data=[
                    "result" => 0,
                    "status" => "Message is already in starred folder"
            ];

        }elseif($curFolder->utf8==$starred->utf8 && $starEmail == 0){
            Log::info("Inside starred folder and email to be unmarked as star", ['file' => __FILE__, 'line' => __LINE__]);
            $tmp = $this->moveToFolder($oClient,$starred,$ids,$inbox);
            $result= $result && $tmp;

            $data=[
                "result" => 1,
                "status" => $result
            ];

        }else{
            if($starEmail){
                Log::info("Inside other folder and email to be moved to starred folder", ['file' => __FILE__, 'line' => __LINE__]);

                $tmp = $this->moveToFolder($oClient,$curFolder,$ids,$starred);
                $result= $result && $tmp;
            }

            $data=[
                "result" => 1,
                "status" => $result
            ];
        }


        return response()->json($data, 200);
    }

    /**
     * search emails for a given text
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Horde_Imap_Client_Exception
     * @throws \Horde_Imap_Client_Exception_NoSupportExtension
     */
    public function search_emails(Request $request) {
        $oClient = $this->oClient;

        $mailbox = $request->input("curfolder");
        $sparam = $request->input("sterm");

        $query = new \Horde_Imap_Client_Search_Query();
        $query->intervalSearch(
            604800, // 604800 = 60 seconds * 60 minutes * 24 hours * 7 days (1 week)
            \Horde_Imap_Client_Search_Query::INTERVAL_YOUNGER
        );
        $query->text($sparam);

        Log::info("Defined query parameters", ['file' => __FILE__, 'line' => __LINE__]);

        $thread = $oClient->thread($mailbox,[
            'criteria' => \Horde_Imap_Client::THREAD_ORDEREDSUBJECT,
            "search" => $query
        ]);

        $allThreads = $thread->getThreads();

        Log::info("Retrieved search results as threads", ['file' => __FILE__, 'line' => __LINE__]);

        $emailThread = [];
        $i=0;
        $uids = [];

        foreach($allThreads as $uthread){
           $curThread = array_keys($uthread);
           $threadCount = count($curThread);

           $emailThread[$i]['uids'] = implode(",",$curThread);
           $emailThread[$i]['count'] = $threadCount;

           $i++;

           array_push($uids,$curThread[$threadCount-1]);
        }

        Log::info("Retrieved latest message of each unique thread", ['file' => __FILE__, 'line' => __LINE__]);

        $uids = new \Horde_Imap_Client_Ids($uids);

        $query = new \Horde_Imap_Client_Fetch_Query();
        $query->envelope();
        $query->structure();

        $messages = $oClient->fetch($mailbox, $query, array('ids' => $uids)); //$results['match']

        $data = [];
        $i=0;

        $indexes = $uids->ids;

        Log::info("Fetched messages with envelope and structure", ['file' => __FILE__, 'line' => __LINE__]);

        foreach($messages as $message){

            $envelope = $message->getEnvelope();
            $part = $message->getStructure();

            $msghdr = new \StdClass;
            $msghdr->recipients = $envelope->to->bare_addresses;
            $msghdr->senders    = $envelope->from->bare_addresses;
            $msghdr->cc         = $envelope->cc->bare_addresses;
            $msghdr->bcc         = $envelope->bcc->bare_addresses;
            $msghdr->subject    = $envelope->subject;
            $msghdr->timestamp  = $envelope->date->getTimestamp();

            $data[$i] = [
                'uid' => $message->getUid(),
                'from' => implode(",",$msghdr->senders),
                'cc' => implode(",",$msghdr->cc),
                'bcc' => implode(",",$msghdr->bcc),
                'to' => implode(",",$msghdr->recipients),
                'date' => $msghdr->timestamp,
                'subject' => $envelope->subject,
                'hasAttachments' => $part->getDisposition(),
                'folder' => $mailbox,
                'body' => '',
                'messageId' =>  $envelope->message_id,
                'thread' => $emailThread[$i]
            ];

           $i++;
        }

        Log::info("Iterated over fetched messages", ['file' => __FILE__, 'line' => __LINE__]);

        return response()->json($data, 200);

    }


    /**
     * @param $oClient
     * @return mixed
     */
    private function getMailBoxes($oClient) {
        $mailBoxes = $oClient->listMailboxes("*");
        Log::info("Get all mailboxes", ['file' => __FILE__, 'line' => __LINE__]);
        return $mailBoxes;
    }

    /**
     * @param $oClient
     * @param $ref
     * @return mixed|string
     */
    private function getMailBox($oClient, $ref) {
        $mailBoxes = $this->getMailBoxes($oClient);
        $flag=0;
        $ref1 = strtolower($ref);
        foreach($mailBoxes as $mailBox){
            $curMailBox = $mailBox['mailbox'];
            $tmp = strtolower($curMailBox->utf8);
            if(strpos($tmp,$ref1) || $tmp==$ref1){
                $flag=1;
                break;
            }
        }
        Log::info("Search for mailbox ".$ref." in existing mailboxes", ['file' => __FILE__, 'line' => __LINE__]);

        if($flag==0){

            try{
                $oClient->createMailbox($ref);
                Log::info("Create mailbox ".$ref, ['file' => __FILE__, 'line' => __LINE__]);
            }catch(\Exception $e){
                report($e);
                Log::error("Unable to create mailbox ".$ref, ['file' => __FILE__, 'line' => __LINE__]);
                return '';
            }

            $mailBoxes = $this->getMailBoxes($oClient);
            foreach($mailBoxes as $mailBox){
                $curMailBox = $mailBox['mailbox'];
                $tmp = $curMailBox->utf8;
                if(strpos($tmp,$ref) || $tmp==$ref){
                    $flag=1;
                    break;
                }
            }
            Log::info("Iterate once again on existing mailboxes and retrieve mailbox ".$ref, ['file' => __FILE__, 'line' => __LINE__]);
        }

        return $curMailBox;
    }


    /**
     * Fetch emails for a folder
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Horde_Imap_Client_Exception
     * @throws \Horde_Imap_Client_Exception_NoSupportExtension
     */
    public function get_emails(Request $request)
    {
        $oClient = $this->oClient;

        $mailbox = $request->input("folder");
        $query = new \Horde_Imap_Client_Search_Query();
        $query->intervalSearch(
            604800, // 604800 = 60 seconds * 60 minutes * 24 hours * 7 days (1 week)
            \Horde_Imap_Client_Search_Query::INTERVAL_YOUNGER
        );

        $thread = $oClient->thread($mailbox,[
            'criteria' => \Horde_Imap_Client::THREAD_ORDEREDSUBJECT,
            "search" => $query
        ]);
        $allThreads = $thread->getThreads();

        $emailThread = [];
        $i=0;
        $uids = [];

        Log::info("Fetch thread in mailbox ".$mailbox, ['file' => __FILE__, 'line' => __LINE__]);

        foreach($allThreads as $uthread){
           $curThread = array_keys($uthread);
           $threadCount = count($curThread);

           $emailThread[$i]['uids'] = implode(",",$curThread);
           $emailThread[$i]['count'] = $threadCount;

           $i++;

           array_push($uids,$curThread[$threadCount-1]);
        }

        Log::info("Iterate over all threads and return latest message in the threads", ['file' => __FILE__, 'line' => __LINE__]);

        $uids = new \Horde_Imap_Client_Ids($uids);

        $query = new \Horde_Imap_Client_Fetch_Query();
        $query->envelope();
        $query->structure();

        $messages = $oClient->fetch($mailbox, $query, array('ids' => $uids));

        $data = [];
        $i=0;

        $indexes = $uids->ids;

        Log::info("Fetch messages by unique thread", ['file' => __FILE__, 'line' => __LINE__]);
        foreach($messages as $message){
            $envelope = $message->getEnvelope();
            $part = $message->getStructure();

            $flags = $message->getFlags();

            $msghdr = new \StdClass;
            $msghdr->recipients = $envelope->to->bare_addresses;
            $msghdr->senders    = $envelope->from->bare_addresses;
            $msghdr->cc         = $envelope->cc->bare_addresses;
            $msghdr->bcc         = $envelope->bcc->bare_addresses;
            $msghdr->subject    = $envelope->subject;
            $msghdr->timestamp  = $envelope->date->getTimestamp();

            $data[$i] = [
                'uid' => $message->getUid(),
                'from' => implode(",",$msghdr->senders),
                'cc' => implode(",",$msghdr->cc),
                'bcc' => implode(",",$msghdr->bcc),
                'to' => implode(",",$msghdr->recipients),
                'date' => $msghdr->timestamp,
                'subject' => $envelope->subject,
                'hasAttachments' => $part->getDisposition(),
                'folder' => $mailbox,
                'body' => '',
                'messageId' =>  $envelope->message_id,
                'thread' => $emailThread[$i],
                'flags' => $flags
            ];

            $i++;
        }
        Log::info("Iterate over messages and return data array", ['file' => __FILE__, 'line' => __LINE__]);


        return response()->json($data, 200);
    }

    /**
     * Fetch email by uid.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Horde_Imap_Client_Exception
     * @throws \Horde_Imap_Client_Exception_NoSupportExtension
     */

    public function get_email(Request $request){
        $oClient = $this->oClient;

        $mailbox = $request->input("folder");

        $thread_uids = explode(",",$request->input('thread_uids'));
        $uids = new \Horde_Imap_Client_Ids($thread_uids);

        $query = new \Horde_Imap_Client_Fetch_Query();
        $query->envelope();
        $query->structure();

        $messages = $oClient->fetch($mailbox, $query, array('ids' => $uids));

        Log::info("Fetch messages from mailbox ".$mailbox ." by uids", ['file' => __FILE__, 'line' => __LINE__]);

        $results = [];
        foreach($messages as $message){

            $envelope = $message->getEnvelope();
            $structure = $message->getStructure();

            $msghdr = new \StdClass;
            $msghdr->recipients = $envelope->to->bare_addresses;
            $msghdr->senders    = $envelope->from->bare_addresses;
            $msghdr->cc         = $envelope->cc->bare_addresses;
            $msghdr->bcc         = $envelope->bcc->bare_addresses;
            $msghdr->subject    = $envelope->subject;
            $msghdr->timestamp  = $envelope->date->getTimestamp();

            $plainpartid = $structure->findBody('plain');
            $htmlpartid  = $structure->findBody('html');

            $id = new \Horde_Imap_Client_Ids($message->getUid());

            $typemap = $structure->contentTypeMap();

            $html_data = $plain_data = '';
            $has_attachment = 0;
            $attachments = [];

            $htmlquery = new \Horde_Imap_Client_Fetch_Query();
            $htmlquery->bodyPart($htmlpartid);

            $htmlmessagedata = $oClient->fetch($mailbox, $htmlquery, array('ids' => $id))->first();
            if(!empty($htmlmessagedata)) {
                $htmlstream = $htmlmessagedata->getBodyPart($htmlpartid, true);

                $htmldata = $structure->getPart($htmlpartid);
                $htmldata->setContents($htmlstream, array('usestream' => true));
                $html_data = $htmldata->getContents();
            }

            if(empty($html_data)) {
                $plainquery = new \Horde_Imap_Client_Fetch_Query();
                $plainquery->bodyPart($plainpartid);

                $plainmessagedata = $oClient->fetch($mailbox, $plainquery, array('ids' => $id))->first();
                if(!empty($plainmessagedata)) {
                    $plainstream = $plainmessagedata->getBodyPart($plainpartid, true);

                    $plaindata = $structure->getPart($htmlpartid);
                    $plaindata->setContents($plainstream, array('usestream' => true));
                    $html_data = $plaindata->getContents();
                }
            }

            foreach($typemap as $part => $type) {
                if(!in_array($part, [$htmlpartid, $plainpartid])){
                    $partdata = $structure->getPart($part);
                    if($file_name = $partdata->getName($part)){
                        $has_attachment = 1;
                        $byte_size = $partdata->getBytes();
                        $attachments[] = [
                            "file" => $file_name,
                            "type" => $partdata->getType(),
                            "size" => $this->humanFileSize($byte_size),
                            "part_id" => $part
                        ];
                    }
                }
            }

            $results[] = [
                'uid' => implode("",$id->ids),
                'from' => implode(",",$msghdr->senders),
                'cc' => implode(",",$msghdr->cc),
                'bcc' => implode(",",$msghdr->bcc),
                'to' => implode(",",$msghdr->recipients),
                'date' => $msghdr->timestamp,
                'subject' => $envelope->subject,
                'body' => $html_data,
                'hasAttachments' => $has_attachment,
                'folder' => $mailbox,
                'messageId' =>  $envelope->message_id,
                'attachment' => $attachments
            ];
        }

        Log::info("Iterate over messages and return the data in a formatted way", ['file' => __FILE__, 'line' => __LINE__]);

        return response()->json($results, 200);

    }

    public function downloadAttachment(Request $request) {
        $user = Auth::user();
        $oClient = $this->get_credentials($user);

        $mailbox = $request->input("mailbox");
        $file_name = $request->input("file_name");
        $part_id = $request->input("part_id");
        $mail_uid = $request->input("mail_uid");

        $thread_id = new \Horde_Imap_Client_Ids($mail_uid);

        $query = new \Horde_Imap_Client_Fetch_Query();
        $query->structure();

        $messages = $oClient->fetch($mailbox, $query, array('ids' => $thread_id));

        foreach($messages as $message){
            $structure = $message->getStructure();

            $partdata = $structure->getPart($part_id);

            $result = [
                'success' => false,
                'message' => 'File could not be found.'
            ];

            $file_content = '';

            if($mail_file_name = $partdata->getName($part_id)){
                if($mail_file_name == $file_name) {
                    $contentquery = new \Horde_Imap_Client_Fetch_Query();
                    $contentquery->fullText();
                    $contentquery->bodyPart($part_id, array(
                        'decode' => true,
                        'peek' => true,
                    ));
                    $contentquery->bodyPartSize($part_id);
                    $filedata = $oClient->fetch($mailbox, $contentquery, array('ids' => $thread_id))->first();
                    if(!empty($filedata)) {
                        $stream = $filedata->getBodyPart($part_id, true);
                        $partdata->setContents($stream, array('usestream' => true));

                        $file_type = $partdata->getType();
                        $file_content = $partdata->getContents();

                        // $result = [
                        //     'file_content' => $partdata->getContents(),
                        //     'file_name' => $file_name,
                        //     'file_type' => $partdata->getType(),
                        //     'success' => true,
                        //     'message' => 'File content fetched successfully.'
                        // ];
                    }
                }
            }
        }
        
        return response($file_content, 200, [
            'Content-type'        => $file_type,
            'Content-Disposition' => 'attachment; filename="' . $file_name . '"',
        ]);
    }

    /**
     * @param $bytes
     * @param int $decimals
     * @return string
     */
    private function humanFileSize($bytes, $decimals = 2) {
        for($i = 0; ($bytes / 1024) > 0.9; $i++, $bytes /= 1024) {}
        return round($bytes, $decimals).['B','kB','MB','GB','TB','PB','EB','ZB','YB'][$i];
    }


    /*start of debug object code*/
    // return an array of superclasses
    /**
     * @param $object
     * @return array|mixed
     * @throws \ReflectionException
     */
    private function getLineage($object){
        $reflection = new \ReflectionClass($object);

        if ($reflection->getParentClass()) {
            $parent = $reflection->getParentClass();

            $lineage = $this->getLineage($parent);
            $lineage[] = $reflection->getName();
        } else {
            $lineage = array($reflection->getName());
        }

        return $lineage;
    }

    /**
     * @param $object
     * @return array
     * @throws \ReflectionException
     */
    private function getChildClasses($object){
        $reflection = new \ReflectionClass($object);

        $classes = get_declared_classes();

        $children = array();

        foreach ($classes as $class) {
            $checkedReflection = new \ReflectionClass($class);

            if ($checkedReflection->isSubclassOf($reflection->getName())) {
                $children[] = $checkedReflection->getName();
            }
        }

        return $children;
    }

    /**
     * @param $object
     * @return \ReflectionMethod[]
     * @throws \ReflectionException
     */
    private function getCallableMethods($object){
        $reflection = new \ReflectionClass($object);
        return $reflection->getMethods();
    }

    /**
     * @param $object
     * @return \ReflectionProperty[]
     * @throws \ReflectionException
     */
    private function getProperties($object){
        $reflection = new \ReflectionClass($object);
        return $reflection->getProperties();
    }

    /**
     * @param $object
     * @throws \ReflectionException
     */
    private function debugObject($object){
        $reflection = new \ReflectionClass($object);
            echo "<h2>Class</h2>";
            echo "<p>{$reflection->getName()}</p>";
            echo "<h2>Inheritance</h2>";
            echo "<h3>Parents</h3>";

            $lineage = $this->getLineage($object);
            array_pop($lineage);
            if (count($lineage) > 0) {
                echo "<p>" . join(" -&gt; ", $lineage) . "</p>";
            } else {
                echo "<i>None</i>";
            }

            echo "<h3>Children</h3>";

            $children = $this->getChildClasses($object);
            echo "<p>";
            if (count($children) > 0) { echo join(', ', $children); }
            else { echo "<i>None</i>"; }

            echo "</p>";
            echo "<h2>Methods</h2>";
            $methods = $this->getCallableMethods($object);
            if (!count($methods)) {
                echo "<i>None</i><br />";
            } else {
                foreach($methods as $method) {
                    echo "<b>{$method}</b>();<br />";
                }
            }

            echo "<h2>Properties</h2>";
            $properties = $this->getProperties($object);
            if (!count($properties)) {
                echo "<i>None</i><br />";
            } else {
                foreach(array_keys($properties) as $property) {
                    echo "<b>\${$property}</b> = " . $object->$property . "<br />";
                }
            }
            echo "<hr />";

            exit;
    }
    /*end of debug object code*/
}
