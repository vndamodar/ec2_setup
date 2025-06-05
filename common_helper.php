the first file is "<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
use Aws\S3\S3Client;

if ( ! function_exists('convertToCurrentTimeZone'))
{
        function convertToCurrentTimeZone($date){
                $CI = & get_instance();
                if(!empty($date)){
                        // date_default_timezone_set('Europe/London');
                        // echo $currentTimeZone = date_default_timezone_get();
                        // print_r($date);
                        // echo changeTimeZone($date, "Europe/London", $currentTimeZone);
                        $datetime = new DateTime('2024-05-21 09:09:40');
                        $datetime->format('Y-m-d H:i:s') . "\n";
                        $la_time = new DateTimeZone($CI->userTimeZone);
                        $datetime->setTimezone($la_time);
                        echo $datetime->format('Y-m-d H:i:s');

                        // timezone by php friendly values
                        // echo date('e');
                        $date = new DateTime($date, new DateTimeZone($CI->userTimeZone));
                        $date->setTimezone(new DateTimeZone('UTC'));
                        $time= $date->format('Y-m-d H:i:s');
                        echo'<br>'. $time;
                        // echo UTC_DATE();
                        // die;
                }
                return false;
        }
}

if ( ! function_exists('getUnreadChatRoom'))
{
        function getUnreadChatRoom($type,$id){
                $CI = & get_instance();
                if(!empty($type) && !empty($id)){
                        if($type == 'agency'){
                                $where_chatroom['agency_id']    = $id;
                        }elseif ($type == 'freelancer') {
                                $where_chatroom['user_id']              = $id;
                        }elseif ($type == 'client') {
                                $where_chatroom['client_id']    = $id;
                        }
                        $where_chatroom['status'] = 1;
                        $roomData = $CI->db->select('chat_room_id')->get_where(CHAT_ROOM,$where_chatroom)->result();
                        if(count($roomData) > 0){
                                $count = 0;
                                foreach ($roomData as $key => $value) {
                                        $read_chat= $CI->db->select('chat_read_id')->get_where(CHAT_READ,['user_id'=>$id,'chat_room_id'=>$value->chat_room_id])->num_rows();
                                        $totalmessage= $CI->db->select('chat_id')->from(CHATS)->where(['sended_by!='=>$id,'chat_room_id'=>$value->chat_room_id])->get()->num_rows();
                                        $unreadMessage = ($totalmessage-$read_chat);
                                        if($unreadMessage > 0){
                                                $count++;
                                        }
                                }
                                return $count;
                        }
                }
                return 0;
        }
}

if (!function_exists('addRecentActivities')){
        function addRecentActivities($data,$type,$autoresponde = ''){
                $CI = &get_instance();
        if($type == 'ACCEPTWORK'){
                        $insertData['job_application_id']       = $data->job_application_id;
                        $insertData['created_by']                       = $data->user_id;
                        $insertData['job_id']                           = $data->job_id;
                        $insertData['type']                                     = $type;
                        $pType                                                          = ($data->milstone_type == 0)?"project":"milestone";
                        if($autoresponde!=''){
                                $insertData['description']              = $autoresponde;
                        }else{
                                $insertData['description']              = "Work accepted for ".$pType." and ".$data->description." of $".$data->releaseAmount.".";
                        }
                        return $CI->db->insert(CONTRACT_ACTIVITIES,$insertData);
                }elseif ($type == 'RELEASEFUND') {
                        $insertData['job_application_id']       = $data->job_application_id;
                        $insertData['created_by']                       = $data->user_id;
                        $insertData['job_id']                           = $data->job_id;
                        $insertData['type']                                     = $type;
                        $pType                                                          = ($data->milstone_type == 0)?"project":"milestone";
                        if($autoresponde!=''){
                                $insertData['description']              = $autoresponde;
                        }else{
                                $insertData['description']              = "Release fund for ".$pType." and ".$data->description." of $".$data->releaseAmount.".";
                        }
                        return $CI->db->insert(CONTRACT_ACTIVITIES,$insertData);
                }elseif ($type == 'SUBMITPROPOSAL') {
                        $insertData['job_application_id']       = $data->job_application_id;
                        $insertData['job_id']                           = $data->job_id;
                        $insertData['type']                                     = $type;
                        $insertData['description']                      = 'Apply For Job';
                        $insertData['created_by']                       = $data->user_id;
                        return $CI->db->insert(CONTRACT_ACTIVITIES,$insertData);
                }elseif ($type == 'SENDOFFER') {
                        $insertData['job_application_id']       = $data->job_application_id;
                        $insertData['job_id']                           = $data->job_id;
                        $insertData['type']                                     = $type;
                        $insertData['description']                      = 'Send Offer of $'.$data->cost.' as '.$data->paidType.' project.';
                        $insertData['created_by']                       = $data->user_id;
                        return $CI->db->insert(CONTRACT_ACTIVITIES,$insertData);
                }elseif ($type == 'FUNDFIRSTMILESTONE') {
                        $milestone = $CI->db->get_where(JOB_MILESTONE,['user_id'=>$data->freelancer_id,'job_id'=>$data->job_id,'isDeleted'=>0])->row('amount');
                        $insertData['job_application_id']       = $data->job_application_id;
                        $insertData['job_id']                           = $data->job_id;
                        $insertData['type']                                     = $type;
                        $insertData['description']                      = 'Activate Milestone of $'.$milestone.' for '.$data->paidType.' project.';
                        $insertData['created_by']                       = $data->user_id;
                        return $CI->db->insert(CONTRACT_ACTIVITIES,$insertData);
                }elseif ($type == 'RAISEDISPUTE') {
                        $insertData['job_application_id']       = $data->job_application_id;
                        $insertData['job_id']                           = $data->jobs_id;
                        $insertData['type']                                     = $type;
                        $insertData['description']                      = 'User has raised dispute.';
                        $insertData['created_by']                       = $data->contract_ended_by;
                        return $CI->db->insert(CONTRACT_ACTIVITIES,$insertData);
                }elseif ($type == 'ESCROWREFUNDED') {
                        $insertData['job_application_id']       = $data->job_application_id;
                        $insertData['job_id']                           = $data->job_id;
                        $insertData['type']                                     = $type;
                        $insertData['description']                      = 'Refund of Milestone: '.$data->description.' for $'.$data->releaseAmount.'.';
                        $insertData['created_by']                       = $data->user_id;
                        return $CI->db->insert(CONTRACT_ACTIVITIES,$insertData);
                }
        }
}

if (!function_exists('escrowBalanceForClient')){
        function escrowBalanceForClient($client_id){
                $CI = &get_instance();
                $CI->db->select('
                        (select SUM(amount) from `job_payment` where `user_id` = "'.$client_id.'" AND `payment_type` = "0") as escrowAmount,
                        (select SUM(amount) from `job_payment` where `user_id` = "'.$client_id.'" AND `payment_type` = "1") as escrowReleaseAmount,
                        (select SUM(amount) from `job_payment` where `user_id` = "'.$client_id.'" AND `payment_type` = "3") as escrowWithdrProposalAmount,
                        (select SUM(amount) from `job_payment` where `user_id` = "'.$client_id.'" AND `payment_type` = "4") as escrowrefundAmount,
                        (select SUM(amount) from `job_payment` where `user_id` = "'.$client_id.'" AND `payment_type` = "5") as escrowDeclineOfferAmount,
                        (select SUM(amount) from `job_payment` where `user_id` = "'.$client_id.'" AND `payment_type` = "6") as escrowOfferExpire,
                        (select SUM(amount) from `job_payment` where `user_id` = "'.$client_id.'" AND `payment_type` = "8") as escrowServiceFeeAmount,
                        (select SUM(amount) from `job_payment` where `user_id` = "'.$client_id.'" AND `payment_type` = "9") as escrowPanCardAmount,
                        (select SUM(amount) from `job_payment` where `user_id` = "'.$client_id.'" AND `payment_type` = "10") as escrowGSTAmount,
                ');
                $CI->db->from(JOB_PAYMENTS.' p');
                $CI->db->where(['p.user_id'=>$client_id]);
                $response  = $CI->db->get()->row();
                $totalEscrowAdd = ($response->escrowAmount+$response->escrowWithdrProposalAmount+$response->escrowDeclineOfferAmount+$response->escrowOfferExpire);
                $totalEscrowsubtract = ($response->escrowReleaseAmount+$response->escrowrefundAmount+$response->escrowServiceFeeAmount+$response->escrowPanCardAmount+$response->escrowGSTAmount);
                return ($totalEscrowAdd - $totalEscrowsubtract);
        }
}

if(! function_exists('created_at_date')){
        function created_at_date(){
                        return date('Y-m-d h:i:s');
        }
}
if(! function_exists('getLocalTimestamp')){
        function getLocalTimestamp($timezone,$time=null){
                // Date for a specific date/time:
                if($time == ''){
                        $date = new DateTime();
                }else{
                        $date = new DateTime($time);
                }
                if($timezone==''){
                        $timezone = 'Asia/Calcutta';
                }
                // Convert timezone
                // $tz = new DateTimeZone($timezone);
                // $date->setTimeZone($tz);
                $date->setTimezone(new DateTimeZone($timezone));

                // Output date after 
                return $date->format('l, F j Y g:i:s A');


                // $date = new DateTime();
                // $timeZoneeeee = $date->getTimezone();
                // echo $timeZoneeeee->getName(); 
                // echo date_default_timezone_get();
                // $date = new DateTime();
                // $date->setTimezone(new DateTimeZone(ini_get('date.timezone')));
                // print_r($date);

                // die;

                // $tz = new DateTimeZone($timezone);
                // $dt = DateTime::createFromFormat('Y-m-d H:i:s', $time, $tz);
                //$dateTime = new DateTime ($time, timezone_open($timezone));
        //$dateTime->setTimezone(new DateTimeZone('Asia/Calcutta'));
        // echo $timezone;
                // echo "<br>";
                // echo $time;
                // echo "<br>";
        // $date = new DateTime($time, new DateTimeZone('Asia/Calcutta'));
                // // echo $date->format('Y-m-d H:i:sP') . "<br>";

                // $date->setTimezone(new DateTimeZone($timezone));
                // echo $date->format('Y-m-d H:i:sP');
                // echo "<br>";
                // echo date('H:i:A',strtotime($date->format('Y-m-d H:i:s A')));
                // echo "<br>";
                // echo "<br>";
                // $dates = new DateTime('2023-04-25 06:07:08', new DateTimeZone('Asia/Calcutta'));
                // // echo $dates->format('Y-m-d H:i:sP') . "<br>";

                // $dates->setTimezone(new DateTimeZone($timezone));
                // echo $dates->format('Y-m-d H:i:sP');
                // echo "<br>";
                // echo date('H:i:A',strtotime($dates->format('Y-m-d H:i:s A')));

                // echo "<br>";
                // echo "<br>";
                // $datess = new DateTime('2023-03-29 18:21:54', new DateTimeZone('Asia/Calcutta'));
                // // echo $datess->format('Y-m-d H:i:sP') . "<br>";

                // $datess->setTimezone(new DateTimeZone($timezone));
                // echo $datess->format('Y-m-d H:i:sP');
                // echo "<br>";
                // echo date('H:i:A',strtotime($datess->format('Y-m-d H:i:s A')));

                // die;
        // return $date->format('Y-m-d H:i:s A');
        }
}
if(! function_exists('getJobDetails')){
        function getJobDetails($job_id){
                $CI = & get_instance();
                $res =  $CI->db->select('j.*,(select GROUP_CONCAT(`skill_name`)  from `qp_role_skill_master` where find_in_set(qp_role_skill_master_id,`j`.`topSkill`) ) as skill_name')->get_where(JOBS.' j',array('j.jobs_id'=>$job_id,'j.job_status'=>1))->row();
                return $res;
        }
}

if(! function_exists('getClientData')){
        function getClientData($user_id){
                $CI = & get_instance();
                $res =  $CI->db->select('cd.company_name as first_name')->from(USER_PROFILE.' up')->join(CLIENT_DETAILS.' cd','cd.user_id = up.created_by')->where(array('up.login_master_id' => $user_id))->get()->row();
                return $res;
        }
}
if(! function_exists('permission')){
        function permission($user_id,$invited_team_id){
                $CI = & get_instance();
                $res =  $CI->db->get_where('members_permission',array('user_id'=>$user_id,'invited_team_id'=>$invited_team_id,'invitation_status'=>'1','status'=>0))->row();
                return $res;
        }
}
if(! function_exists('is_account_active')){
        function is_account_active($id){
                $CI = & get_instance();
                $r = false;
                $res=   $CI->db->get_where('login_master',array('login_master_id'=>$id,'is_active'=>'Y','is_deleted'=>'N'))->result();
        //      echo  $CI->db->last_query();
                if(count($res) > 0){
                        $r = true;
                }
                return $r;
        }
}

if ( ! function_exists('uploadimageBase'))
{
        /*
        function uploadimageBase($data){
                $CI = & get_instance();
                $randnumber = rand(9999,1000);
                $name = $randnumber.'project_'.$CI->user_data->login_user.'.jpg';

        $uploadPath='upload/project_image/'.$CI->user_data->login_user;
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0777, true);
            copy($CI->config->item('aws_url').'/index.html', $uploadPath.'/index.html');
            copy($CI->config->item('aws_url').'/index.html', "./upload/project_image/".$CI->user_data->login_user.'/index.html');
        }
        $source = fopen($data, 'r');
        $destination = fopen($uploadPath.'/'.$name, 'w');
        stream_copy_to_stream($source, $destination);

        fclose($source);
        fclose($destination);
                return $name;
        }
        */
        function uploadimageBase($data){

                $CI = & get_instance();
                  // Load the model
                $CI->load->model('S3_model');
                $randnumber = rand(9999,1000);
                $name = $randnumber.'project_'.$CI->user_data->login_user.'.jpg';

        $uploadPath='upload/project_image/'.$CI->user_data->login_user;
                $fileName = $uploadPath.'/'.$name;
                // Upload to S3
                $s3Url = $CI->S3_model->upload_base64($data, $fileName);

        // if (!file_exists($uploadPath)) {
        //     mkdir($uploadPath, 0777, true);
        //     copy($CI->config->item('aws_url').'/index.html', $uploadPath.'/index.html');
        //     copy($CI->config->item('aws_url').'/index.html', "./upload/project_image/".$CI->user_data->login_user.'/index.html');
        // }
        // $source = fopen($data, 'r');
        // $destination = fopen($uploadPath.'/'.$name, 'w');
        // stream_copy_to_stream($source, $destination);

        // fclose($source);
        // fclose($destination);
                return $name;
        }
}

if(! function_exists('originUrl')){
        function originUrl() {
                 
                return $_SERVER['HTTP_ORIGIN'];
        }
}

if(! function_exists('addhttp')){
        function addhttp($url) {
                if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
                    $url = "http://" . $url;
                }
                return $url;
        }
}

if(! function_exists('getPostIdByComment')){
        function getPostIdByComment($commentID){
                $CI = & get_instance();
                return $CI->db->get_where('comments',array('comments_id'=>$commentID))->row('posts_id');
        }
}


if( ! function_exists('slug_str')){
        function slug_str($string) {
                $cardName =  explode(" ",preg_replace('/\s+/', ' ',strtolower($string)));
                $string2 = str_replace('#', ' ', $cardName); // Replaces all spaces with hyphens.
                $cardName1 =  preg_replace('/[^A-Za-z0-9\-]/', '', $string2); // Removes special chars.
                $alias =  implode("-",$cardName1);
                return $alias;
        }                
}

if ( ! function_exists('size_as_kb'))
{
        function size_as_kb($size)
        {
                if ($size < 1024) {
                        return "{$size} bytes";
                } elseif ($size < 1048576) {
                        $size_kb = round($size/1024);
                        return "{$size_kb} KB";
                } else {
                        $size_mb = round($size/1048576, 1);
                        return "{$size_mb} MB";
                }
        }
}
if ( ! function_exists('notification'))
{
        function notification($data){
                $CI = & get_instance();

                return $CI->db->insert(NOTIFICATIONS,$data);
        }
}


if ( ! function_exists('getSingleName'))
{

        function getSingleName($table,$column,$value,$name){
                $CI = & get_instance();
                return $CI->db->get_where($table,array($column=>$value))->row($name);
        }
}

if ( ! function_exists('moveFiles'))
{
        function moveFiles($data){
                $CI = & get_instance();
                $source_file = str_replace('//','/',$data['from_path']);
                $destination_path = str_replace('//','/',$data['to_path']);
                if (!file_exists($destination_path)) {
            mkdir($destination_path, 0777, true);
        }
                $res  = rename($source_file, $destination_path . pathinfo($source_file, PATHINFO_BASENAME));
                return $res;
        }
}

if ( ! function_exists('getUserId'))
{
        function getUserId($table,$column,$value){
                $CI = & get_instance();
                return $CI->db->get_where($table,array($column=>$value))->row();
        }
}

if ( ! function_exists('getJobId'))
{
        function getJobId($id){
                $CI = & get_instance();
                return $CI->db->where('uniq_id',$id)->or_where('jobs_id',$id)->get(JOBS)->row('jobs_id');
        }
}

if ( ! function_exists('getLoginMasterIdByuuid'))
{ 
        function getLoginMasterIdByuuid($postData=''){
                if($postData==''){
                        $postData = json_input();
                }
                $CI = & get_instance();
                return  (int) $CI->db->get_where(LOGIN_MASTER,['uuid'=>explode('-',$postData['data'][0])[2]])->row('login_master_id');
        }
}
if ( ! function_exists('getParentIdByuuid'))
{ 
        function getParentIdByuuid($postData='',$type =''){
                if($postData==''){
                        $postData = json_input();
                }
                $CI = & get_instance();
                $created_by='';
                $login_master_id =   (int) $CI->db->get_where(LOGIN_MASTER,['uuid'=>explode('-',$postData['data'][0])[2]])->row('login_master_id');
                // print_r($login_master_id);
                // die;
                if($type == 'freelancer'){
                        $userData = $CI->db->get_where(USER_PROFILE,array('login_master_id'=>$login_master_id))->row();
                        // print_r($userData);
                        // die;
                        if($userData != '' && $userData->profileType == 3){
                                // echo 'if'.$userData->login_master_id;
                                $created_by = $userData->login_master_id;
                        }else{
                                // echo 'else';
                                $created_by = $CI->db->get_where(USER_PROFILE,array('created_by'=>$login_master_id,'profileType'=>3))->row('login_master_id');
                        }
                        // echo $created_by;
                        // die;
                }elseif($type == 'agency'){
                        $created_by = $CI->db->get_where(USER_PROFILE,array('login_master_id'=>$login_master_id))->row('created_by');
                }else{
                        $created_by = $CI->db->get_where(USER_PROFILE,array('login_master_id'=>$login_master_id))->row('created_by');
                }
                // echo $created_by;die;
                return $created_by;
        }
}

if ( ! function_exists('getLoginMasterIdById'))
{ 
        function getLoginMasterIdById($type,$id){
                $CI = & get_instance();
                $res = $CI->db->get_where(USER_PROFILE,['login_master_id'=>$id])->row('created_by');
                // echo $CI->db->last_query();
                // die;
                if($res!='' && $type != ''){
                        return (int) $CI->db->get_where(USER_PROFILE,['created_by'=>$res,'profileType'=>$type])->row('login_master_id');
                }
                return $id;
        }
}

if ( ! function_exists('getCretedByUsingId'))
{ 
        function getCretedByUsingId($postData){
                $CI = & get_instance();
                if($postData!=''){
                        $created_by = $CI->db->get_where(USER_PROFILE,array('login_master_id'=>$postData))->row('created_by');
                        return $created_by;
                }else{
                        return false;
                }
        }
}

if ( ! function_exists('created_by'))
{
        function created_by($id = null){
                $CI = & get_instance();
                $created_by = $CI->db->get_where(USER_PROFILE,array('login_master_id'=>($id == null)?$CI->user_data->login_user:$id))->row('created_by');
                if(!$created_by){
                        $created_by = $CI->db->get_where(USER_PROFILE,array('user_detail_id'=>($id == null)?$CI->user_data->login_user:$id))->row('created_by');
                }
                return $created_by;
        }
}
if ( ! function_exists('getProfileType'))
{
        function getProfileType(){
                $CI = & get_instance();
                $profileType = $CI->db->get_where(USER_PROFILE,array('login_master_id'=>created_by(),'accountType'=>'0'))->row('profileType');
                if(!$profileType){
                        $profileType = $CI->db->get_where(USER_PROFILE,array('user_detail_id'=>created_by(),'accountType'=>'0'))->row('profileType');
                }
                return $profileType;
        }
}

if ( ! function_exists('getJobsactivities'))
{
        function getJobsactivities($job_id){
                $CI = & get_instance();
                $return =array('total_application'=>0,'interviewing'=>0,'send_invites'=>0,'unanswerd_invites'=>0);
                $return['total_application'] = $CI->db->get_where(JOBS_APPLICATION,['jobs_id'=>$job_id])->num_rows();
                $return['interviewing'] = $CI->db->get_where(INVITED_JOB,['job_id'=>$job_id,'action'=> 1])->num_rows();
                $return['send_invites'] = $CI->db->get_where(INVITED_JOB,['job_id'=>$job_id])->num_rows();
                $return['unanswerd_invites'] = $CI->db->get_where(INVITED_JOB,['job_id'=>$job_id,'action'=> 0])->num_rows();
                return $return;
        }
}

if ( ! function_exists('getjobSuccessRate'))
{
        function getjobSuccessRate($postData){
                $CI = & get_instance();

                $where['user_id'] = (isset($postData)&& $postData!='')?$postData:'';
                $total = $CI->db->from(JOBS_APPLICATION)->where($where)->where_in('application_status',[1,2,3,6,7,8])->count_all_results();
                $completed = $CI->db->from(JOBS_APPLICATION)->where($where)->where(['application_status'=>6])->count_all_results();
                $percent =  ($completed/$total)*100;
                return   ((string)$percent != 'NAN')?$percent:0;
                die;
        }
}

if ( ! function_exists('getAssociatedWith'))
{
        function getAssociatedWith($postData,$type=null){
                $CI = & get_instance();
                $res = [];
                $where['m.user_id'] = (isset($postData)&& $postData!='')?$postData:'';
                $where['m.invitation_status'] = 1;
                $where['m.status']=0;
                if($type!=null && $type == 1 ){
                        $where['m.invited_acc_type']=$type;
                        $select = '`u`.`profileType`,`u`.`quality_professional_flag`,`u`.`first_name`,`u`.`last_name`,`u`.`email_id`,`u`.`profile_picture_path`,`u`.`login_master_id`';
                        $mainAgency = $CI->db->select($select)->get_where(USER_PROFILE.' u',['u.created_by'=>$where['m.user_id'],'u.profileType'=>1])->row();
                        if($mainAgency !=''){
                                if($mainAgency->profileType == 1){
                                        $company = $CI->db->select('company_name,company_picture_path')->get_where(COMPANY_DETAILS,['user_id'=>$mainAgency->login_master_id])->row();
                                        $mainAgency->first_name = $company->company_name;
                                        $mainAgency->last_name='';
                                        $mainAgency->profile_picture_path = $CI->config->item('aws_url').'/profile_image/'.$mainAgency->profile_picture_path;
                                }else{
                                        $mainAgency->profile_picture_path = $CI->config->item('aws_url').'/profile_image/'.$mainAgency->profile_picture_path;
                                }
                                $mainAgency->agency_type = 1;
                                array_push($res,$mainAgency);
                        }
                }elseif($type != 'all'){
                        $where['m.invited_acc_type']=$type;
                }
                $data = $CI->db->select('`ud`.`profileType`,`ud`.`quality_professional_flag`,`ud`.`first_name`,`ud`.`last_name`,`ud`.`email_id`,`ud`.`profile_picture_path`,`ud`.`login_master_id`,`m`.`agency_contract as agency_type`,`ud`.`created_by`,`m`.`invited_acc_type`')->from(USER_PROFILE.' ud')->join(MEMBERS_PERMISSION.' m','m.created_by = ud.login_master_id')->where($where)->get()->result();
                // print_r($data);
                // die;
                foreach ($data as $key => $value) {
                        if($value->invited_acc_type == 1){
                                $company = $CI->db->select('company_name,company_picture_path')->get_where(COMPANY_DETAILS,['created_by'=>$value->created_by])->row();
                                $value->first_name = $company->company_name;
                                $value->last_name='';
                                $value->profile_picture_path = $CI->config->item('aws_url').'/profile_image/'.$company->company_picture_path;
                                $value->profileType = $value->invited_acc_type;
                        }elseif($value->invited_acc_type == 2){
                                $clientData = $CI->db->select('company_name,image')->get_where(CLIENT_DETAILS,['user_id'=>$value->created_by])->row();
                                $value->first_name = $clientData->company_name;
                                $value->last_name='';
                                $value->profile_picture_path = $CI->config->item('aws_url').'/profile_image/'.$clientData->image;
                                $value->profileType = $value->invited_acc_type;
                        }
                        array_push($res,$value);

                }
                return $res;
        }
}

if ( ! function_exists('getExclusiveAssociated'))
{
        function getExclusiveAssociated($postData){
                $CI = & get_instance();
                $exclusive_id =$CI->db->get_where(USER_PROFILE,['login_master_id'=>$postData])->row('exclusive_id');
                if($exclusive_id > 0){
                        $select           = '`u`.`profileType`,`u`.`quality_professional_flag`,`u`.`first_name`,`u`.`last_name`,`u`.`email_id`,`u`.`profile_picture_path`,`u`.`login_master_id`';
                        $response   = $CI->db->select($select)->get_where(USER_PROFILE.' u',['u.login_master_id'=>$exclusive_id])->row();
                        if($response !=''){
                                if($response->profileType == 1){
                                        $company = $CI->db->select('company_name,company_picture_path')->get_where(COMPANY_DETAILS,['user_id'=>$response->login_master_id])->row();
                                        $response->first_name = $company->company_name;
                                        $response->last_name='';
                                        $response->profile_picture_path = $CI->config->item('aws_url').'/profile_image/'.$company->company_picture_path;
                                }else{
                                        $response->profile_picture_path = $CI->config->item('aws_url').'/profile_image/'.$response->profile_picture_path;
                                }
                                $response->agency_type = 1;
                        // print_r($response);
                        // die;
                        }
                        return $response;
                }
                return false;
        }
}

if ( ! function_exists('getExclusiveAssociatedDetails'))
{
        function getExclusiveAssociatedDetails($exclusive_id){
                $CI = & get_instance();
                if($exclusive_id > 0){
                        $select           = '`u`.`profileType`,`u`.`quality_professional_flag`,`u`.`first_name`,`u`.`last_name`,`u`.`email_id`,`u`.`profile_picture_path`,`u`.`login_master_id`,`u`.`created_by`';
                        $response   = $CI->db->select($select)->get_where(USER_PROFILE.' u',['u.login_master_id'=>$exclusive_id])->row();
                        if($response !=''){
                                if($response->profileType == 1){
                                        $company = $CI->db->select('company_name,company_picture_path')->get_where(COMPANY_DETAILS,['created_by'=>$response->created_by])->row();
                                        $response->first_name = $company->company_name;
                                        $response->last_name='';
                                        $response->profile_picture_path = $CI->config->item('aws_url').'/profile_image/'.$company->company_picture_path;
                                }elseif($response->profileType == 2){
                                        $clientData = $CI->db->select('company_name,image')->get_where(CLIENT_DETAILS,['user_id'=>$response->created_by])->row();
                                        $response->first_name = $clientData->company_name;
                                        $response->last_name  = '';
                                        $response->profile_picture_path = $CI->config->item('aws_url').'/profile_image/'.$clientData->image;
                                }
                                // $response->agency_type = 1;
                        }
                        return $response;
                }
                return false;
        }
}

if ( ! function_exists('getApplyAgencyDetails'))
{
        function getApplyAgencyDetails($exclusive_id,$type){
                $CI = & get_instance();
                if($type=='agency'){
                        $CI->db->select('u.login_master_id,u.created_by,c.company_name as first_name,c.company_picture_path as profile_picture_path');
                        $CI->db->from(USER_PROFILE.' u');
                        $CI->db->join(COMPANY_DETAILS.' c','c.created_by = u.created_by');
                        $CI->db->where('u.login_master_id',$exclusive_id);
                }else{
                        $CI->db->from(USER_PROFILE.' u');
                        $CI->db->where('u.login_master_id',$exclusive_id);
                }
                $response =$CI->db->get()->row();
                if($response){
                        $response->profile_picture_path = $CI->config->item('aws_url').'/profile_image/'.$response->profile_picture_path;
                }

                return $response;
        }
}

if ( ! function_exists('getTotalEarn'))
{
        function getTotalEarn($postData){
                $CI = & get_instance();

                $CI->db->select_sum('amount');
                $CI->db->from('job_milestone');
                $CI->db->where('user_id',$postData);
                $CI->db->where('paymentStatus',1);
                $earn = $CI->db->group_by('user_id')->get()->row();
                return   ($earn->amount>0)?$earn->amount:0;
        }
}

if ( ! function_exists('getClientId'))
{
        function getClientId(){
                $CI = & get_instance(); 
                $created_by = $CI->db->get_where(USER_PROFILE,array('created_by'=>created_by(),'profileType'=>2))->row('login_master_id');
                if(!$created_by){
                        $created_by = $CI->db->get_where(USER_PROFILE,array('created_by'=>created_by(),'profileType'=>2))->row('login_master_id');
                } 
                return $created_by;
        }
}
if ( ! function_exists('getLoginUserDetail'))
{
        function getLoginUserDetail($userID){
                $CI = & get_instance();
                return $CI->db->get_where(USER_PROFILE,array('login_master_id'=>$userID))->row();
        }
}

if ( ! function_exists('getUuid'))
{

        function getUuid($userID){
                $CI = & get_instance();
                return $CI->db->get_where(LOGIN_MASTER,array('login_master_id'=>$userID))->row('uuid');
        }
}


if ( ! function_exists('getCreatedByEmail'))
{
        function getCreatedByEmail($id){
                $CI = & get_instance();
                $createdByID =  $CI->db->get_where(USER_PROFILE,array('login_master_id'=>$id))->row('created_by');

                $login_name = $CI->db->get_where('login_master',array('login_master_id'=>$createdByID))->row('login_name');

                return $login_name;
        }
}

if ( ! function_exists('loginUserType'))
{
        function loginUserType($user_id){
                $CI = & get_instance();
                $created_by = $CI->db->get_where(USER_PROFILE,array('login_master_id'=>$user_id))->row('profileType');
                //$created_by =  created_by();
                // $sess = $CI->db->get_where(USER_LOGIN_SESS,array('user_id'=>$created_by))->row();
                if($created_by == 1){
                        $type = '1';
                }elseif($created_by == 2){
                        $type = '2';
                }elseif($created_by == 3){
                        $type = '3';
                }
                return $created_by;
        }
}
/*
        * Method to load css files into your project.
        * @param array $css
        */
if ( ! function_exists('user_type'))
{
        function user_type($id)
        {
                $return  = 'user';
                if($id==2){
                        $return ='company';
                }
                return $return;
        }
}



if( ! function_exists('getDnsIp') )
{
        function getDnsIp($SiteUrl){

                $SiteUrl = str_replace('http://', '', $SiteUrl ); 
                $SiteUrl = str_replace('https://', '', $SiteUrl ); 

                $result = dns_get_record($SiteUrl); 

                if($result[0]['type']=='A' ||$result[0]['type']=='C' || $result[0]['type']=='TXT' && $result[0]['ip']!="" ||  $result[0]['host']!="" ){
                        return true;
                }else{
                        return false;
                }

        }
}
if( ! function_exists('sent_mail_multiple') )
{

        function sent_mail_multiple($emailList,$subject,$message,$attach="") {
                //echo 'ttttttt====='.$email;
                //error_reporting(-1);
                //ini_set('display_errors', 1);
                $email = array_map(function($item) {
    return $item->login_name;
}, $emailList);
        $CI = & get_instance();
                $CI->load->library('email');

                // Email configuration
                $config = array(
                        'protocol'  => 'smtp',
                        'smtp_host' => 'smtp.mandrillapp.com',
                        'smtp_port' => 587,
                        'smtp_user' => 'Qapin Inc', // Replace with your Mandrill username
                        'smtp_pass' => MAILCHIMP,  // Replace with your Mandrill API key
                        'smtp_crypto' => 'tls', // You can also use 'ssl'
                        'mailtype'  => 'html',
                        'charset'   => 'utf-8',
                        'wordwrap'  => TRUE,
                        'newline'   => "\r\n"
                );
            $config['smtp_timeout'] = 10;
                $config['crlf'] = "\r\n";
                $config['validate'] = TRUE;
                $config['smtp_debug'] = 2;  // This will give us more detailed output.

                $CI->email->initialize($config);

                // Email content
                $CI->email->from('qapin@qapin.com', 'Qapin'); // Replace with your email and name
                $CI->email->bcc(implode(',', $email)); 
                 $CI->email->to('qapin@qapin.com');           // Replace with recipient email
                $CI->email->subject($subject);
                $CI->email->message($message);

                // Send email
                if ($CI->email->send()) {
                //      echo "Email sent successfully!";
                        return true;

                } else {
                        return false;
                }
    }
}

if( ! function_exists('sent_mail') )
{

        function sent_mail($email,$subject,$message,$attach="") {
        error_reporting(-1);
        ini_set('display_errors', 1);
        $CI = & get_instance();
                $CI->load->library('email');

                // Email configuration
                $config = array(
                        'protocol'  => 'smtp',
                        'smtp_host' => 'smtp.mandrillapp.com',
                        'smtp_port' => 587,
                        'smtp_user' => 'Qapin Inc', // Replace with your Mandrill username
                        'smtp_pass' => MAILCHIMP,  // Replace with your Mandrill API key
                        'smtp_crypto' => 'tls', // You can also use 'ssl'
                        'mailtype'  => 'html',
                        'charset'   => 'utf-8',
                        'wordwrap'  => TRUE,
                        'newline'   => "\r\n"
                );  
                $CI->email->initialize($config);

                // Email content
                $CI->email->from('qapin@qapin.com', 'Qapin'); // Replace with your email and name
                $CI->email->to($email); 
                // $CI->email->cc('damodar@qapin.com');           // Replace with recipient email
                $CI->email->subject($subject);
                $CI->email->message($message);

                // Send email
                if ($CI->email->send()) {
                //      echo "Email sent successfully!";
                        return true;

                } else {
                        // echo $CI->email->print_debugger(); // Print error if email sending fails
                        return false;
                }

                 // Load email library
                // $CI = & get_instance();       
                // $CI->load->library('mailchimp_lib');

                // $email = $email;
        // $subject = $subject;
        // $message = $message;

        // $response = $CI->mailchimp_lib->sendEmail($email, $subject, $message);

        // // Handle response
        // echo $response; // Handle response as needed
                // return true;
    }
}
if( ! function_exists('sent_mail0') )
{
         function sent_mail0($email,$subject,$message,$attach="") {
        // Load email library
                $CI = & get_instance();  
                $CI->load->library('email');

        // Configure email parameters
        $config = array(
            'protocol' => 'smtp',  // Choose your preferred email protocol (smtp, sendmail, mail)
            'smtp_host' => 'smtp.gmail.com',
            'smtp_port' => '587',
            'smtp_user' => 'qapininc@gmail.com',
            'smtp_pass' => 'ciia mztv dkdd yezp',
            'mailtype' => 'html',  // Set email format to HTML
            'charset' => 'utf-8',
                        'newline' => "\r\n",
                        'smtp_crypto' => 'tls'
        );

        // Initialize email library with configuration
        $CI->email->initialize($config);
        // Set email parameters
        $CI->email->from('qapininc@gmail.com', 'Qapin');
        $CI->email->to($email);
        $CI->email->subject($subject);
        $CI->email->message($message);

        // Send email
        if ($CI->email->send()) {
           echo 'Email sent successfully.';
        } else {
          //  echo 'Email could not be sent.';
            echo $CI->email->print_debugger();  // Print email debug information
        }
        die;
        return true;
    }
        function sent_mail1($email,$subject,$message,$attach=""){

                $api_key = getenv('SENDGRID_API_KEY'); 
                $endpoint = 'https://api.sendgrid.com/v3/mail/send';

                $data = array(
                    'personalizations' => array(
                        array(
                            'to' => array(
                                array(
                                    'email' => $email,
                                    'name' => 'Recipient Name'
                                )
                            ),
                            'subject' => $subject
                        )
                    ),
                    'from' => array(
                        'email' => 'damodar@qapin.com',
                        'name' => 'Qapin'
                    ),
                    'content' => array(
                        array(
                            'type' => 'text/html',
                            'value' => $message
                        )
                    )
                ); 
                // print_r($data);

                $data_string = json_encode($data);

                $ch = curl_init($endpoint);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $api_key
                ));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                $response = curl_exec($ch);

                if (curl_errno($ch)) {
                    echo 'Error: ' . curl_error($ch);
                }
                // else{
                //      echo 'fuck';
                // echo curl_errno($ch);

                // }

                curl_close($ch);
                // echo $response; 
                // print_r($response);
                // die;
                return true;
              
        }
} 

if( ! function_exists('user_ip') )
{
        /**
         * Function to get user ip address
         *
         * @return string
         */
        function user_ip()
        {
                $CI = & get_instance();
                return $CI->input->ip_address();
        }
}



if (!function_exists('format_number')) {
        function format_number($number = ""){
                return number_format($number, 0, ',',',');
        }
}

if (!function_exists('pr')) {
    function pr($data, $type = 0) {
        print '<pre>';
        print_r($data);
        print '</pre>';
        if ($type != 0) {
            exit();
        }
    }
}

if (!function_exists('filter_input_xss')){
        function filter_input_xss($input_array){


        $CI =& get_instance();
        $out_array = array();
        if(is_array($input_array)){
                foreach ($input_array as $key => $value)
                {
                        if(is_array($value)){

                                $out_array[$key] = $value;
                                //$out_array[$key] = $value!="" ? $CI->security->xss_clean(implode(',',$value)) : '';

                        }else{
                                $out_array[$key] = $value!="" ? htmlspecialchars_decode(htmlspecialchars($CI->security->xss_clean($value,ENT_QUOTES))): '';
                        }
                }
        }else{
                if($input_array)
                  $out_array= htmlspecialchars_decode(htmlspecialchars($input_array, ENT_QUOTES));

        }
                 
        return $out_array;

      
        }
}

if (!function_exists('segment')){
        function segment($index){
                $CI = &get_instance();
        if($CI->uri->segment($index)){
                  return $CI->uri->segment($index);
        }else{
            return false;
        }
        }
}

if (!function_exists('post')){
        function post($input,$check=true){
                $CI = &get_instance();
        if($check){
                  return filter_input_xss($CI->input->post($input));
        }else{
            return $CI->input->post($input);
        }
        }
}

if (!function_exists('get')){
        function get($input){
                $CI = &get_instance();
                return filter_input_xss($CI->input->get($input));
        }
}

if (!function_exists('json_input')){
        function json_input($input=''){
                $CI = &get_instance();

                $postData = array_merge($_POST,json_decode(file_get_contents('php://input'),true));
                if($input!=""){
                                return filter_input_xss($postData[$input]);
                }else{
                        return filter_input_xss($postData);
                }
        }
}

if (!function_exists('session')){
        function session($input){
                $CI = &get_instance();
                return $CI->session->userdata($input);
        }
}

if (!function_exists('set_session')){
        function set_session($name,$input){
                $CI = &get_instance();
                return $CI->session->set_userdata($name,$input);
        }
}

        if (!function_exists('unset_session')){
                function unset_session($name){
                        $CI = &get_instance();
                        return $CI->session->unset_userdata($name);
                }
        }

        if (!function_exists('array_flatten')) {
                function array_flatten($data) { 
                        $it =  new RecursiveIteratorIterator(new RecursiveArrayIterator($data));
                        $l = iterator_to_array($it, false);
                        return $l;
                } 
        }

        if (!function_exists('send_sms')) {
                function send_sms($data){
                        //Your authentication key
                        $authKey = "100878AzvmCguo9zJ5dbd8aeb"; //

                        //Multiple mobiles numbers separated by comma
                        $mobileNumber = $data['moblie_no'];

                        //Your message to send, Add URL encoding here.
                        $message = urlencode($data['message']);

                        //Define route 
                        $route          =       "4";
                        $senderId       =       "QAPINN";
                        if(isset($changeRoute)){ $route = "1"; }

                        //Prepare you post parameters
                        $postData = array(
                        'authkey' => $authKey,
                        'mobiles' => $mobileNumber,
                        'message' => $message,
                        'sender' => $senderId,
                        'route' => $route
                        );

                        //API URL
                        $url="https://control.msg91.com/api/sendhttp.php";

                        // init the resource
                        $ch = curl_init();
                        curl_setopt_array($ch, array(
                        CURLOPT_URL => $url,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_POST => true,
                        CURLOPT_POSTFIELDS => $postData
                        //,CURLOPT_FOLLOWLOCATION => true
                        ));

                        //Ignore SSL certificate verification
                        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

                        //get response
                        $output = curl_exec($ch);


                        return true;
                }
        }

        if (!function_exists('Qa_Secure')) {
                function Qa_Secure($string, $censored_words = 1, $br = true, $strip = 0) {
                ///     trim(preg_replace('/\s\s+/', ' ', $string))
                        $string = trim($string);
                        $string = cleanString(htmlspecialchars_decode($string));
                 
                        $string = htmlspecialchars($string, ENT_QUOTES);
                        if ($br == true) {
                                $string = str_replace('\r\n', " <br>", $string);
                                $string = str_replace('\n\r', " <br>", $string);
                                $string = str_replace('\r', " <br>", $string);
                                $string = str_replace('\n', " <br>", $string);
                        } else {
                                $string = str_replace('\r\n', "", $string);
                                $string = str_replace('\n\r', "", $string);
                                $string = str_replace('\r', "", $string);
                                $string = str_replace('\n', "", $string);
                        }
                        if ($strip == 1) {
                                $string = stripslashes($string);
                        }
                        $string = str_replace('&amp;#', '&#', $string);
                        /*if ($censored_words == 1) {
                                global $config;
                                $censored_words = @explode(",", $config['censored_words']);
                                foreach ($censored_words as $censored_word) {
                                        $censored_word = trim($censored_word);
                                        $string        = str_replace($censored_word, '****', $string);
                                }
                        }*/
                        return $string;
                }
        }

        if (!function_exists('Qa_IsMobile')) {
                function Qa_IsMobile() {
                        $useragent = $_SERVER['HTTP_USER_AGENT'];
                                if(preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i',$useragent)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($useragent,0,4))) {
                                        return true;
                        }
                        return false;
                }
        }

        if (!function_exists('cleanString')) {
                function cleanString($string) {
                        return $string = preg_replace("/&#?[a-z0-9]+;/i","", $string); 
                }
        }

        if (!function_exists('Qa_ImportImageFromUrl')) {
                function Qa_ImportImageFromUrl($media, $custom_name = '_url_image') {
                        global $wo;
                        if (empty($media)) {
                                return false;
                        }
                        if (!file_exists('upload/post_image/' . date('Y'))) {
                                mkdir('upload/post_image/' . date('Y'), 0777, true);
                        }
                        if (!file_exists('upload/post_image/' . date('Y') . '/' . date('m'))) {
                                mkdir('upload/post_image/' . date('Y') . '/' . date('m'), 0777, true);
                        }
                        //$size      = getimagesize($media);
                        $extension = 0; //image_type_to_extension($size[2]);
                        if (empty($extension)) {
                                $extension = '.jpg';
                        }
                        $dir               = 'upload/post_image/' . date('Y') . '/' . date('m');
                        $file_dir          = $dir . '/' . $custom_name . $extension;
                        $fileget           = fetchDataFromURL($media);
                        if (!empty($fileget)) {
                                $importImage = @file_put_contents($file_dir, $fileget);
                        }
                        if (file_exists($file_dir)) {
                                //$upload_s3 = Wo_UploadToS3($file_dir);
                                $check_image = getimagesize($file_dir);
                                if (!$check_image) {
                                        unlink($file_dir);
                                }
                                return $file_dir;
                        } else {
                                return false;
                        }
                }
        }

        if (!function_exists('fetchDataFromURL')) {
                function fetchDataFromURL($url = '') {
                        if (empty($url)) {
                                return false;
                        }
                        $ch = curl_init($url);
                        curl_setopt( $ch, CURLOPT_POST, false );
                        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
                        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0);
                        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0);
                        curl_setopt( $ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.0; en-US; rv:1.7.12) Gecko/20050915 Firefox/1.0.7");
                        curl_setopt( $ch, CURLOPT_HEADER, false );
                        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
                        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
                        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 5);
                        curl_setopt( $ch, CURLOPT_TIMEOUT, 5);
                        return curl_exec( $ch );
                }
        }

        if (!function_exists('Qa_Markup')) {
                function Qa_Markup($text, $link = true, $hashtag = false, $mention = false,$post_id = 0,$comment_id = 0,$reply_id = 0) {
                        $email = true;
                        //Wo_UserData
                        if ($mention == true) {
                                $Orginaltext = $text;
                                $mention_regex = '/@\[([0-9]+)\]/i';
                                if (preg_match_all($mention_regex, $text, $matches)) {
                                        foreach ($matches[1] as $match) {
                                                $match         = Qa_Secure($match);
                                                $match_user    = Qa_UserData($match);
                                                 $match_search  = '@[' . $match . ']';
                                                if (isset($match_user->user_detail_id)) {


                                                         
                                                        $match_replace = '<span class="user-popover" data-id="' . $match_user->user_detail_id . '" data-type="' . $match_user->quality_professional_flag . '"><a  target="blank" href="'.profile_url($match_user).'" >' . $match_user->first_name .' '. $match_user->last_name .  '</a></span>';
                                                          $text = str_replace($match_search, $match_replace, $text);

                                                        //die;
                                                }
                                                /*else{
                                                        $match_replace = '';
                                                        $Orginaltext = str_replace($match_search, $match_replace, $Orginaltext);
                                                        $text = str_replace($match_search, $match_replace, $text);
                                                        if (!empty($post_id)) {
                                                                mysqli_query($sqlConnect, "UPDATE " . T_POSTS . " SET `postText` = '".$Orginaltext."' WHERE `id` = {$post_id}");
                                                        }
                                                        elseif (!empty($comment_id)) {
                                                                mysqli_query($sqlConnect, "UPDATE " . T_COMMENTS . " SET `text` = '".$Orginaltext."' WHERE `id` = {$comment_id}");
                                                        }
                                                        elseif (!empty($reply_id)) {
                                                                mysqli_query($sqlConnect, "UPDATE " . T_COMMENTS_REPLIES . " SET `text` = '".$Orginaltext."' WHERE `id` = {$reply_id}");
                                                        }
                                                } */
                                        }
                                }
                        }
                        if ($link == true) {
                                //echo  $text;
                        //      $link_search = '/\[a\](.*?)\[\/a\]/i';
                                $link_search = '~[a-z]+://\S+~';
                                if (preg_match_all($link_search, $text, $matches)) {
                                        //print_r($matches);
                                        foreach ($matches[0] as $match) {
                                                //echo $match;
                                                $match_decode     = urldecode($match);
                                                $match_decode_url = $match_decode;
                                                $count_url        = mb_strlen($match_decode);
                                                if ($count_url > 50) {
                                                        $match_decode_url = mb_substr($match_decode_url, 0, 30) . '....' . mb_substr($match_decode_url, 30, 20);
                                                }
                                                $match_url = $match_decode;
                                                if (!preg_match("/http(|s)\:\/\//", $match_decode)) {
                                                        $match_url = 'http://' . $match_url;
                                                }

                                                 $text = str_replace($match, '<a href="' . strip_tags($match_url) . '" target="_blank" class="hash" rel="nofollow">' . $match_decode_url . '</a>', $text);
                                        }
                                }
                        }
                        if ($hashtag == true) {
                                //$hashtag_regex = '/(#\[([0-9]+)\])/i';
                                $hashtag_regex = '/(#\w+)/';
                        //      echo $text;
                                preg_match_all($hashtag_regex, $text, $matches);
                        //      print_r($matches);
                                $match_i = 0;
                                foreach ($matches[1] as $match) {
                                        $hashtag  = $matches[1][$match_i];
                                        $hashkey  = $matches[2][$match_i];
                                        //$hashdata = Qa_GetHashtag($hashkey);
                                        //print_r($hashdata);
                                        //if (is_array($hashdata)) {
                                                //$hashlink = '<a href="#" class="hash">#' . $hashdata['tag'] . '</a>';
                                                $hashlink = '<a href="#" class="hash">' . $hashtag . '</a>';
                                                $text     = str_replace($hashtag, $hashlink, $text);
                                        //}
                                        $match_i++;
                                }
                        }

                        if ($email == true) {
                                $email_regex = '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/i';
                                preg_match_all($email_regex, $text, $matches);
                                foreach ($matches[0] as $match) {
                                        $match_decode     = urldecode($match);
                                                $match_decode_url = $match_decode;
                                                $count_url        = mb_strlen($match_decode);
                                                if ($count_url > 50) {
                                                        $match_decode_url = mb_substr($match_decode_url, 0, 30) . '....' . mb_substr($match_decode_url, 30, 20);
                                                }
                                                $match_url = $match_decode;
                                                 

                                                 $text = str_replace($match, '<a href="mailto:' . strip_tags($match_url) . '" target="_blank" class="hash" rel="nofollow">' . $match_decode_url . '</a>', $text);
                                }
                        }
                        return $text;
                }

        }

        if (!function_exists('Qa_checkSpecialChar')) {
                function Qa_checkSpecialChar($value){
                        //preg_replace("/(\r?\n){2,}/", "\n\n", $text);
                        //      And to address the problem of some sending \r only:
                        // return badString.Replace("&", "&amp;").Replace("\"", "&quot;").Replace("'", "&apos;").Replace(">", "&gt;").Replace("<", "&lt;");
                         //     preg_replace("/[\r\n]{2,}/", "\n\n", $text);
                         //preg_replace('/\s\s+/', ' ', $value)
                        return  trim(str_replace('\n\n','<br><br>',str_replace('&quot;','"',str_replace('&amp;','&',preg_replace('/[\r\n]{2,}/', '\n\n', $value)))));
                }
        }

        if (!function_exists('Qa_removeBrFromString')) {
                function Qa_removeBrFromString($value){
                        return  trim(str_replace('<br><br>','\n\n',$value));
                }
        }

        if(! function_exists('CountryName')){
                function CountryName($country_id){
                        $CI = & get_instance();
                        $res =  $CI->db->get_where('country',array('country_id'=>$country_id))->row('country_name');
                        return $res;
                }
        }

        if(! function_exists('StateName')){
                function StateName($state_id){
                        $CI = & get_instance();
                        $res =  $CI->db->get_where('state',array('state_id'=>$state_id))->row('state_name');
                        return $res;
                }
        }

        if(! function_exists('CityName')){
                function CityName($city_id){
                        $CI = & get_instance();
                        $res =  $CI->db->get_where('city',array('city_id'=>$city_id))->row('city_name');
                        return $res;
                }
        }


        if (!function_exists('Qa_GetHashtag')) {
                function Qa_GetHashtag($tag = '', $type = true) {
                        $CI = & get_instance();

                        $create = false;
                        if (empty($tag)) {
                                return false;
                        }
                        $tag     = Qa_Secure($tag);
                        $md5_tag = md5($tag);
                        if (is_numeric($tag)) {
                                $query = " SELECT * FROM " . HASHTAGS . " WHERE `id` = {$tag}";
                        } else {
                                $query  = " SELECT * FROM " . HASHTAGS . " WHERE `hash` = '{$md5_tag}' ";
                                $create = true;
                        }
                        $Res   = $CI->db->query($query)->result();

                        $week        = date('Y-m-d', strtotime(date('Y-m-d') . " +1week"));
                        if (count($Res) > 0) {
                                $Res = $Res[0];

                                $data   = array(
                                        'id' => $Res->id,
                                        'hash' => $Res->hash,
                                        'tag' => $Res->tag,
                                        'last_trend_time' =>  $Res->last_trend_time,
                                        'trend_use_num' =>  $Res->trend_use_num,
                                );
                                        return $data;
                        } elseif (count($Res)  == 0 && $type == true) {
                                if ($create == true) {
                                        $hash          = md5($tag);
                                        $query_two     = " INSERT INTO " . HASHTAGS . " (`hash`, `tag`, `last_trend_time`,`expire`) VALUES ('{$hash}', '{$tag}', " . time() . ", '$week')";
                                        $Res2   = $CI->db->query($query_two);//->result();
                                        $sql_id = $CI->db->insert_id();
                                        if ($sql_id!='') {
                                                $data   = array(
                                                        'id' => $sql_id,
                                                        'hash' => $hash,
                                                        'tag' => $tag,
                                                        'last_trend_time' => time(),
                                                        'trend_use_num' => 0
                                                );
                                                return $data;
                                        }
                                }
                        }
                }
        }
?>