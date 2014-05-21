<?php

class Email
{

    /**
     * ARKADE LOYALTY PLATFORM MODEL
     *
     * This model is platform-agnostic.
     * Anywhere in the app, if a developer instantiates this model, each of the functions
     * (assuming they are configured correctly) should work straight out of the box.
     *
     * SENDING A ONCE-OFF MAIL:
     * The model depends on pre-configured email templates in each platform.
     * To send an email, simply use the queue_email() method with the required params.
     *
     *
     * HOW MAILS ARE SENT:
     * Every minute, the system picks 5 emails to send.
     * (WHY ONLY 5? -- Exact Target uses SOAP, which is super-slow.)
     *
     * Using the parameters from the email_queue table,
     * it uses the selected platform to send the mail.
     *
     * The template in the platform inserts the variables and sends the mail.
     * Every API reply (along with parameters sent to the platform) is saved
     * in the email_logs table, for reference.
     *
     *
     *
     * CHECKING IF A CUSTOMER EXISTS IN A PLATFORM DB
     *
     * Use does_customer_exist() with the required params.
     * It will return an array of customer details if they exist, or false.
     *
     *
     * GETTING A CUSTOMER'S DETAILS
     *
     * Use get_customer_details() with the required params.
     * NOTE: This is an alias of does_customer_exist(), so no need to use them side-by-side.
     *
     *
     * REGISTERING A CUSTOMER TO THE EMAIL PLATFORM
     *
     * Use register_customer() with the required params.
     * The call will reply with JSON on how the request went.
     *
     */

    /**
     * There are 3 available email platforms:
     *
     * - Exact Target
     * - Traction
     * - Mandrill
     */


    // Set a few persistent variables
    public $active_email_platform;
    public $email_platform;
    public $api_key;
    public $api_secret;
    public $user_name;
    public $password;
    public $endpoint_url;


    /**
     * @param null $api_key
     * The api key for your selected platform. If required.
     *
     * @param null $api_secret
     * The api secret for your selected platform. If required.
     *
     * @param null $endpoint_url
     * The password for your selected platform. If required. Usually only used in Traction.
     */


    public function __construct($api_key = null, $api_secret = null, $endpoint_url = null)
    {

        // Set Exact Target username & pass based on ENV file settings
        $this->et_username = $_ENV['ET_USERNAME'];
        $this->et_password = $_ENV['ET_PASSWORD'];

        // First, check that an email platform is selected within the app.
        $email_platforms = new EmailPlatforms();
        // show_active_platform() will cause an exception if there's no active platform set up.
        $active_email_platform = $email_platforms->show_active_platform();

        // Set the authentication details. A few of these will be null usually.
        $this->api_key = $api_key;
        $this->api_secret = $api_secret;

        $this->endpoint_url = $endpoint_url;

        // Set some platform shortnames for use down in the class.
        switch (strtolower($active_email_platform)) {
            case "exact target":
                $this->email_platform = "exact_target";
                $this->user_name = $_ENV['ET_USERNAME'];
                $this->password = $_ENV['ET_PASSWORD'];
                break;
            case "traction":
                $this->email_platform = "traction";
                break;
            case "mandrill":
                $this->email_platform = "mandrill";
                break;
        }
    }



    /**
     * Queue an email to be sent through an email platform
     *
     * @param $email
     * Person's email address
     *
     * @param $template_id
     * The ID of the Email platform template being used
     *
     * @param array $merge_vars
     * An array of key-value merge vars set into the email template
     *
     * @param string $person_id
     * The internal person ID, so that email sends can be attributed to them
     *
     * @param null $to_name
     * The person's name
     *
     * @param null $from_name
     * A from name, if you like
     *
     * @param null $from_email
     * A from email, if you like.
     *
     * @return mixed
     */
    public function queue($email, $template_id, $merge_vars = [], $person_id = '0', $to_name = null, $from_name = null, $from_email = null)
    {
        $email_queue = new EmailQueue();

        $data_array                     = [];
        $data_array['person_id']        = $person_id;
        $data_array['email_platform']   = $this->email_platform; // Set in the construct
        $data_array['email']            = $email;
        $data_array['merge_vars']       = $merge_vars;
        $data_array['template_id']      = $template_id;
        $data_array['to_name']          = $to_name;
        $data_array['from_name']        = $from_name;
        $data_array['from_email']       = $from_email;
        $data_array['api_reply']        = [];
        $data_array['api_email_id']     = '';
        $data_array['retries']          = '0';
        $data_array['done']             = '0';
        $data_array['timestamp']        = date("Y-m-d H:i:s");

        $email_queue->json_data = json_encode($data_array);

        $success = $email_queue->save();

        return $success;
    }


    /**
     * ABSTRACTED METHODS
     */

    public function does_customer_exist($email)
    {
        $method = $this->email_platform . "_does_customer_exist";

        return $this->$method($email);
    }


    public function get_customer_details($email)
    {
        return $this->does_customer_exist($email);
    }

    public function register_customer($email, $firstname, $lastname, $list_id = null)
    {
        $method = $this->email_platform . "_register_customer";

        return $this->$method($email, $firstname, $lastname, $list_id);
    }


    public function email_send_single($email, $merge_vars = [], $template_id, $to_name = null, $from_name = null, $from_email = null)
    {
        $method = $this->email_platform . "_email_send_single";

        return $this->$method($email, $merge_vars, $template_id, $to_name, $from_name, $from_email);
    }




    /**
     * PLATFORM-SPECIFIC METHODS
     */


    /**
     * MANDRILL
     */

    private function mandrill_does_customer_exist($email)
    {
        return 'Mandrill is not a user-store, it is a transactional email service only.';
    }

    private function mandrill_register_customer($email, $firstname, $lastname, $list_id = null)
    {
        return 'Mandrill is not a user-store, it is a transactional email service only.';
    }

    private function mandrill_email_send_single($email, $merge_vars = array(), $template_id = null, $to_name = null, $from_name = null, $from_email)
    {
        $mandrill = new Mandrill($_ENV['MANDRILL_API_KEY']);

        $message = array(
            'to' => array(
                array(
                    'email' => $email,
                    'name' => $to_name
                )
            ),
            'merge_vars' => array(
                array(
                    'rcpt' => $email,
                )
            )
        );

        $template_name = $template_id;


        $template_content = array();

        if(count($merge_vars) >= 1)
        {
            foreach ($merge_vars as $key => $value) {
                $this_merge = array(
                    'name' => $key,
                    'content' => $value
                );
                $template_content[] = $this_merge;
            }
        }


        $response = $mandrill->messages->sendTemplate($template_name, $template_content, $message);
        return Response::json($response);
    }


    /**
     * EXACT TARGET
     *
     * NOTE: ET USES SOAP!!!!!!!!!! SOAP!!!!!!! Calls take ages. AGES!!
     */

    private function exact_target_does_customer_exist($email)
    {
        $exact_target = new druid628\exactTarget\EtClient($this->user_name, $this->password);

        // Subscriber object is passed the client
        $subscriber = new druid628\exactTarget\EtSubscriber($exact_target);

        $subscriberKey = $emailAddress = $email;

        // Find a subscriber from ExactTarget by subscriber key (email address is optional)
        $subscriber->find($subscriberKey, $emailAddress);

        // If status is null (they do not exist) - return false.
        if ($subscriber->Status == null) {
            return false;
        }

        // The status is not null!

        $reply_array = array();
        $reply_array['status'] = $subscriber->Status;
        $reply_array['firstname'] = null;
        $reply_array['lastname'] = null;
        $reply_array['email'] = $email;


        // Set some user attributes
        foreach ($subscriber->Attributes as $attribute) {

            if ($attribute->Name == "firstname") {
                $reply_array['firstname'] = $attribute->Value;
            }

            if ($attribute->Name == "lastname") {
                $reply_array['lastname'] = $attribute->Value;
            }

        }

        return $reply_array;
    }

    private function exact_target_register_customer($email, $firstname, $lastname, $list_id = null)
    {
        $exact_target = new druid628\exactTarget\EtClient($this->user_name, $this->password);

        // Subscriber object is passed the client
        $subscriber = new druid628\exactTarget\EtSubscriber($exact_target);

        $subscriberKey = $emailAddress = $email;

        // Find a subscriber from ExactTarget by subscriber key (email address is optional)
        $subscriber->find($subscriberKey, $emailAddress);

        // FIRSTNAME

        // Build a new Attribute
        $newAttrib = new druid628\exactTarget\EtAttribute();
        $newAttrib->setName('firstname'); // Attribute Name
        $newAttrib->setValue($firstname); // Attribute Value

        $newAttrib = new druid628\exactTarget\EtAttribute();
        $newAttrib->setName('lastname'); // Attribute Name
        $newAttrib->setValue($lastname); // Attribute Value

        // setAttributes is be used for initially setting attributes on subscriber
        $subscriber->setAttributes(array($newAttrib));

        // Update / Save Subscriber record
        $subscriber->save();

        return $subscriber;
    }


    private function exact_target_email_send_single($email, $merge_vars = array(), $template_id = null, $to_name = null, $from_name = null, $from_email)
    {

        $client = new druid628\exactTarget\EtClient($this->user_name, $this->password);

        $ts = $client->buildTriggeredSend($template_id);

        $sub = new druid628\exactTarget\EtSubscriber();
        $sub->EmailAddress = $email;
        $sub->SubscriberKey = $email;


        $attributes_array = array();

        foreach ($merge_vars as $key => $value) {
            $attr = new druid628\exactTarget\EtAttribute();
            $attr->Name = $key;
            $attr->Value = $value;

            $attributes_array[] = $attr;
        }

        $sub->Attributes = $attributes_array;


        $ts->setSubscribers(array($sub)); // Array of Subscribers
        $email_sent = $client->sendEmail($ts, 'TriggeredSend');

        return $email_sent;
    }


    /**
     * TRACTION
     */

    private function traction_does_customer_exist($email)
    {
        return 'Traction library not installed: This function is not set up yet.';
    }


    private function traction_register_customer($email, $firstname, $lastname, $list_id = null)
    {
        return 'Traction library not installed: This function is not set up yet.';
    }

    private function traction_email_send_single($email, $merge_vars = array(), $template_id = null, $to_name = null, $from_name = null, $from_email)
    {
        return 'Traction library not installed: This function is not set up yet.';
    }


}
