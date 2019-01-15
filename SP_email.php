<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

function sparkpostAPI(){
  GLOBAL $toName, $toEmail, $fromName, $fromEmail, $subj, $body, $cc, $bcc, $attachments, $mimeMessage;

  $payload = [];
  if($mimeMessage === ""){
    $filePath = "";
    $fileName = "";
    $fileData = "";
    $fileType = "";
    $recipientsArray = array();
    $ccBccRecipientsArray = array();

    $payload = [
        'options' => ['sandbox' => false,],
        'content' => [
            'from' => [
                'name' => $fromName,
                'email' => $fromEmail,
            ],
            'subject' => $subj,
            'html' => $body,
        ],
    ];

    // ======= Format Array of Recipients: =======
    $toEmailNoSpace = rtrim(str_replace(' ','',$toEmail), ',');
    $toEmailArray = explode(",",$toEmailNoSpace);
    foreach ($toEmailArray as $to){
        $recipientsArray[] =  array('address' => $to);
    }
    // ====== END ================

    // ===== Format CC or BCC Array ========
    if ($bcc !== '' || $cc !== ''){
      $ccEmailNoSpace = rtrim(str_replace(' ','',$cc), ',');
      $bccEmailNoSpace = rtrim(str_replace(' ','',$bcc), ',');
      $ccArray = explode(",",$ccEmailNoSpace);
      $bccArray = explode(",",$bccEmailNoSpace);
      $combo = array_merge($ccArray, $bccArray);
      foreach ($combo as $to){
        if ($to !== ""){
          $recipientsArray[] =  array('address' => $to, 'header_to' => $toEmail );
        }
      }
      if ($cc !== ''){
        $payload['content']['headers']= array('CC' => $cc);
      }
    }
    // ===== END =====

    // Add recipients to payload object:
    $payload['recipients'] = $recipientsArray;

    // ===== Variables for Attachment: ==========
    if ($attachments !== ""){
       $filePath = $attachments;
       $fileName = substr($attachments, strrpos($attachments, '/') + 1);
       $fileData = base64_encode(file_get_contents($filePath));
       $fileExtension = substr($attachments, strrpos($attachments, '.') + 1);
       $fileType = 'application/'. $fileExtension;

       $payload['content']['attachments']= array(array('name' => $fileName, 'type' => $fileType, 'data' => $fileData,));
     }
    // ===== END ==========

  }
  else{
    $payload = [
      'options' => ['sandbox' => false,],
      'content' => [
        'email_rfc822' => $mimeMessage
        ],
      'recipients' => [
          ['address' => $toEmail]
    ],
  ];
  }

  // ========= Access SparkPost API: ==============
  function sparkpost($method, $uri, $payload = [], $headers = [])
  {
      $defaultHeaders = [ 'Content-Type: application/json' ];
      $curl = curl_init();
      $method = strtoupper($method);
      $finalHeaders = array_merge($defaultHeaders, $headers);
      $url = 'https://api.sparkpost.com:443/api/v1/'.$uri;

      curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
      curl_setopt($curl, CURLOPT_URL, $url);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

      if ($method !== 'GET') {
          curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));
      }

      curl_setopt($curl, CURLOPT_HTTPHEADER, $finalHeaders);
      $result = curl_exec($curl);
      curl_close($curl);
      return $result;
  }

  // ==================================================================
  $headers = [ 'Authorization: API KEY' ];
  echo "Sending email...\n";


  $email_results = sparkpost('POST', 'transmissions', $payload, $headers);
  return $email_results;

}
?>
