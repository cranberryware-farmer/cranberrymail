<?php
/**
 * Implements calls to SMTP server
 *
 * PHP Version 7.3
 *
 * @category Productivity
 * @package  CranberryMail
 * @author   CranberryWare Development Team (NetTantra Technologies) <support@oss.nettantra.com>
 * @license  GNU AGPL-3.0 https://github.com/cranberryware/cranberrymail/blob/master/LICENSE
 * @link     https://github.com/cranberryware/cranberrymail
 */
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;

/**
 * Implements all required methods for SMTP calls
 *
 * @category Controller
 * @package  Cranberrymail
 * @author   CranberryWare Development Team (NetTantra Technologies) <support@oss.nettantra.com>
 * @license  GNU AGPL-3.0 https://github.com/cranberryware/cranberrymail/blob/master/LICENSE
 * @link     https://github.com/cranberryware/cranberrymail
 */
class SmtpController extends Controller
{
    /**
     * Loads SMTP configs
     *
     * @return array
     */
    public function getSmtp(): array
    {
        $smtp = [
            "host" => env("SMTP_HOST"),
            "port" => env("SMTP_PORT"),
            "encryption" => env("SMTP_ENCRYPTION")
        ];
        Log::info(
            "Return smtp values from .env",
            ['file' => __FILE__, 'line' => __LINE__]
        );
        return $smtp;
    }

    /**
     * Separate string to multiple emails
     *
     * @param string $emails Concatenated email list
     *
     * @return string|string[]
     */
    private function _sepEmails($emails)
    {
        if (stripos($emails, ",")) {
            $result = explode(',', $emails);
            Log::info(
                "Convert emails into an array",
                ['file' => __FILE__, 'line' => __LINE__]
            );

        } else {
            $result = $emails;
            Log::info(
                "No change in emails",
                ['file' => __FILE__, 'line' => __LINE__]
            );
        }
        return $result;
    }

    /**
     * Sends an email through SMTP server
     *
     * @param Request $request Laravel Request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendEmail(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'to'=>'required',
                'cc'=>'nullable',
                'bcc' => 'nullable',
                'subject' => 'required',
                'body' => 'required',
                'attachment' => 'nullable|max:20000'
            ]
        );

        Log::info(
            "Validate input parameters",
            ['file' => __FILE__, 'line' => __LINE__]
        );

        if ($validator->fails()) {
            Log::error(
                "Please check the size of your attachment",
                ['file' => __FILE__, 'line' => __LINE__]
            );
            return response()->json(
                [
                    "result" => 0,
                    "message" => "Error: please check the size of your attachment."
                ], 200
            );
        }

        try{
            $user = Auth::user();
            $from = $user->email;
            $password=Crypt::decryptString($user->key);

            $tos = $request->input("to");
            $to = $this->_sepEmails($tos);
            $ccs = $request->input("cc");
            $bccs = $request->input("bcc");
            $subject = $request->input("subject");
            $body=$request->input("body");
            $msgId=$request->input('messageId');
            $draft_id = $request->input('draft_id');

            if ($draft_id) {
                $draft_folder = $request->session()->get('draft_folder', '');

                $oClient = $this->getIMAPCredential();

                $ids = new \Horde_Imap_Client_Ids($draft_id);
                $oClient->store(
                    $draft_folder,
                    ['ids' => $ids, 'add' => '\deleted']
                );

                Log::info(
                    "Added deleted flag to given emails in trash folder",
                    ['file' => __FILE__, 'line' => __LINE__]
                );

                $draft_deleted = $oClient->expunge(
                    $draft_folder, ['ids' => $ids,'list' => true]
                );
            }

            $smtp = $this->getSmtp();

            $name = explode("@", $from);

            $flag=0;
            $env=env("APP_ENV");
            if ($smtp['encryption'] == "starttls") {
                $smtp['encryption'] = "tls";
                $flag=1;
                Log::info(
                    "Encryption from starttls to tls",
                    ['file' => __FILE__, 'line' => __LINE__]
                );
            }


            $transport = new \Swift_SmtpTransport(
                $smtp['host'], $smtp['port'], $smtp['encryption']
            );
            $transport->setUsername($from);
            $transport->setPassword($password);

            if ($flag == 1 || $env == 'local') {
                $transport->setStreamOptions(
                    ['ssl' => ['allow_self_signed' => true, 'verify_peer' => false]]
                );
                Log::info(
                    "Allow self signed certificate in swiftmailer",
                    ['file' => __FILE__, 'line' => __LINE__]
                );
            }

            $swift_mailer = new \Swift_Mailer($transport);
            if ($request->hasFile('attachment')) {

                $msg = (new \Swift_Message($subject))
                    ->setFrom([ $from => $name[0]])
                    ->setTo($to);

                $files = $request->file('attachment');
                foreach ($files as $file) {
                    $msg->attach(
                        \Swift_Attachment::fromPath($file->getPathName())
                            ->setFilename($file->getClientOriginalName())
                    );
                }
                Log::info(
                    "Attach files to email",
                    ['file' => __FILE__, 'line' => __LINE__]
                );
            } else if ($request->input("attachmentURLs")) {
                $msg = (new \Swift_Message($subject))
                    ->setFrom([ $from => $name[0]])
                    ->setTo($to);
                $attached_urls = $request->input("attachmentURLs");
                $urls_arr = json_decode($attached_urls, true);
                foreach ($urls_arr as $file) {
                    $file_path = storage_path('app/') . $file["file"];
                    $msg->attach(
                        \Swift_Attachment::fromPath($file_path)
                            ->setFilename($file["file"])
                    );
                }
            } else {
                $msg = (new \Swift_Message($subject))
                    ->setFrom([ $from => $name[0]])
                    ->setTo($to);
                Log::info(
                    "No file attachments",
                    ['file' => __FILE__, 'line' => __LINE__]
                );
            }

            if (!empty($ccs)) {
                $cc = $this->_sepEmails($ccs);
                $msg->setCc($cc);
                Log::info("Set CC", ['file' => __FILE__, 'line' => __LINE__]);
            }

            if (!empty($bccs)) {
                $bcc = $this->_sepEmails($bccs);
                $msg->setBcc($bcc);
                Log::info("Set BCC", ['file' => __FILE__, 'line' => __LINE__]);
            }

            $msg->setBody($body, 'text/html');
            $msg->addPart(strip_tags($body), "text/plain");

            if (!empty($msgId)) {
                $headers = $msg->getHeaders();
                $headers->addTextHeader('In-Reply-To', $msgId);
                $headers->addTextHeader('References', $msgId);
                Log::info("Set Headers", ['file' => __FILE__, 'line' => __LINE__]);
            }

            $result = $swift_mailer->send($msg);
            $msg = "Success, email sent";

            Log::info("Email Sent", ['file' => __FILE__, 'line' => __LINE__]);
        } catch (\Exception $e) {
            report($e);
            $result = -1;
            $msg = $e->getMessage();
            Log::error($msg, ['file' => __FILE__, 'line' => __LINE__]);
        }

        return response()->json(["result" => $result, "message" => $msg], 200);
    }
}
