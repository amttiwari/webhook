<?php
defined('BASEPATH') OR exit('No direct script access allowed');
// require APPPATH . '/libraries/PHPMailer/PHPMailerAutoload.php';
require APPPATH . '/libraries/class.phpmailer.php';
class Admin extends CI_Controller {

	var $login_id;// "global" items
	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see https://codeigniter.com/user_guide/general/urls.html
	 */
	function __construct()
	{
		parent::__construct();
		// $this->load->helper(array('common_helper'));
		// if($this->session->userdata('admin_type') != 'admin')
		// {
		// 	$this->session->set_userdata('admin_type','');
		// 	$this->session->set_userdata('login_id','');
		// 	redirect('Home/index');
		// }
		$this->login_id = $this->session->userdata('login_id');
	}
	public function import_user()
	{
		

		error_reporting(0);
		$import_user =0;
    // Allowed mime types
    $csvMimes = array('text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel', 'text/plain');
    
    // Validate whether selected file is a CSV file
    if(!empty($_FILES['file']['name']) && in_array($_FILES['file']['type'], $csvMimes)){
        
        // If the file is uploaded
        if(is_uploaded_file($_FILES['file']['tmp_name'])){
            
            // Open uploaded CSV file with read-only mode
            $csvFile = fopen($_FILES['file']['tmp_name'], 'r');
            
            // Skip the first line
            fgetcsv($csvFile);
            
            // Parse data from CSV file line by line
            while(($line = fgetcsv($csvFile)) !== FALSE){
                // Get row data
              		 $name   = $line[0];
                     $email  = $line[1];
                     $password  = $line[2];
                     $country_code  = $line[3];
                     $mobile  = $line[4];
		       
		        $conditions ='email ="'.$email.'" || mobile="'.$mobile.'" ';
				$check = $this->common_model->getAllRecordsById('users',$conditions);		
				if($check[0] =='')
				{
					$post_data = array(
						"fullname"=> $name,
						"email"=>$email,
						"country_code"=>$country_code,
						"mobile"=>$mobile,
						"active_status"=>1,
						"password"=>md5($password),
						"admin_id"=>$this->login_id
					);

					$newid = $this->common_model->addRecords('users',$post_data);
	                $import_user++;
				}
				               
            }
            
            // Close opened CSV file
            fclose($csvFile);
            
           die($import_user. ' User Successfully Imported.');
        }else{
            die('Please try again !');
        }
    }else{
        
        die('Invalid File !');
    }
	}

	public function email_confirmation($to_email,$subject,$msg)
        {  
           $path = base_url();
           // $this->mailerclass();
           $mail = new PHPMailer();  
           $mail->IsSMTP(); 
           $mail->isHTML(true); 
           $mail->SMTPDebug = 1;  
           $mail->SMTPAuth = true;  
           $mail->Host = 'smtp.gmail.com';
           $mail->Port =465;
           $mail->Timeout = 3600;     
           $mail->Username = 'amit.espsofttech@gmail.com';  
           $mail->Password = 'amit123#';      
           $mail->SMTPSecure = "ssl";    
           $mail->SetFrom('amit.espsofttech@gmail.com');
           $mail->Subject = $subject; 
           $mail->Body = $msg;
           $mail->AltBody = "";
           $mail->AddAddress($to_email);
           if(!$mail->Send()) 
           {
               $error = 'Mail error: '.$mail->ErrorInfo; 
           }
         }


        function mailerclass()
        {
            
        } 

	public function index()
	{
		$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
		$this->load->view('admin/login',$data);
		
	}
	public function login()
	{		
			$email = $this->input->post('email');
			$password = $this->input->post('password');
			$check_login = $this->common_model->admin_login("users", array('email' =>$email, 'password' => MD5($password),'usertypeid'=>1));
	       if(empty($check_login))
	       {
	          $this->session->set_flashdata('error',  'Email or password does not match !');
	          redirect('admin');
	       }else{

	       		$this->session->set_userdata('admin_name',$check_login[0]->fullname);	       	   
	       			if($check_login[0]->active_status == 0)
	       			{
	       				$this->session->set_flashdata('error',  'Your account has been deactivated. Please contact to admin ');
	          			redirect('admin');
	       			} 
	       			$uid = $check_login[0]->id;
	       			/*create coin wallet code start here*/
			         $crypto_coins = array('ETH');
			          foreach ($crypto_coins as $key => $coin)
			          {
			            $walletInfo = $this->common_model->getSingleRecordById('coin_address_info',array('user_id' => $uid));
			            if(empty($walletInfo))
			            {              
			                if($coin == "ETH"){
			                    $apiurl = "52.66.202.69:7000/api/eth/create_wallet";
			                    $result = $this->common_model->curl_url_get($apiurl);
			                    $private_key = $result->data->wallet->private; 
			                    $public_key  = $result->data->wallet->public;
			              }              
			              if(@$result->code){
			                  $user_coin_id = $this->common_model->addRecords('coin_address_info',array(
			                                'user_id' => $uid,                                
			                                'private' => @$private_key,
			                                'public'  => @$public_key,
			                                'original_address' => @$public_key,                                
			                                'create_date' => date('Y-m-d H:i:s'),
			                                'update_date' => date('Y-m-d H:i:s')));
			            }
			          }
			        }
		       /*create coin wallet code end */    			

	       			$this->session->set_userdata('login_id',$check_login[0]->id);
	       			//$this->session->set_userdata('admin_code',$check_login[0]->admincode);
	       			//$this->session->set_userdata('admin_language',$check_login[0]->language);
	       			//$this->session->set_userdata('admin_type','admin');	
	       			redirect('admin/dashboard');      		    		
	       	  	
	       }	    

	}

	public function dashboard()
	{
		$adminid = $this->session->userdata('login_id');

		$q = " SELECT unread_notification_count($adminid) as count";
		$data['notifi_count'] = $this->common_model->getRowByQuery($q);	

		$query="SELECT id as notification_id, getnotificationtype(notification_type_id) as type,link, recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,isread,get_duration(datetime) as duration FROM notification where recipient_id=$adminid and isread=0";
    	$data['notifi_list']= $this->common_model->getArrayByQuery($query);
    	
		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));
		$query="SELECT count(id) as count from users where usertypeid= 2 AND active_status=1 ";
        $usercount= $this->common_model->getArrayByQuery($query);
        $data['issuer_count']=$usercount[0]['count'];
       
        $query="SELECT count(id) as count from users where usertypeid= 3 AND active_status=1 ";
        $usercount= $this->common_model->getArrayByQuery($query);
        $data['investor_count']=$usercount[0]['count'];
        
        $query="SELECT count(id) as count from asset ";
        $usercount= $this->common_model->getArrayByQuery($query);
        $data['assets_count']=$usercount[0]['count'];  
        $data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
	 	$this->load->view('admin/dashboard',$data);
	}

	public function read_notification()
	{
		$link = $_GET['link'];
		$id = $_GET['id'];
		$this->common_model->updateRecords("notification",array('isread'=>1),array('id'=>$id));
		redirect($link);

	}
	

	public function flash_notification()
	{
		$adminid = $this->session->userdata('login_id');
		$query="SELECT getnotificationtype(notification_type_id) as type,recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,link,isread,get_duration(datetime) as duration FROM `notification` where recipient_id=$adminid and is_flash=0";
    $recdata= $this->common_model->getArrayByQuery($query);
    $condition = array('recipient_id' => $adminid);
    $dataArray = array(
        'is_flash'=>1
        );
    $this->common_model->updateRecords('notification', $dataArray, $condition);
  	echo  json_encode($recdata);
	}
	public function add_issuer()
	{

		if(isset($_POST['add_issuer']))
		{
			$data = $this->input->post();
			$password = $data['password'];
			$con_password = $data['con_password'];
			if($password!=$con_password)
			{
				$this->session->set_flashdata('error','Password And confirm password does not matched');
				redirect('admin/add_issuer');
			}
			$dataArray = array(
								'companyname' => $data['company_name'],
								'fname' => $data['fname'],
								'lname' => $data['lname'],
								'email' => $data['email'],
								'mobile' => $data['phoneno'],
								'city' => $data['address'],
								'usertypeid' =>2,
								'password' => md5($data['password'])
									);
			$USERid = $this->common_model->addRecords("users",$dataArray);
			$recdata=file_get_contents('http://espsofttech.in/TokenAPI/api/redblock_token/create_wallet');
              $data=json_decode($recdata,true);
               if($data['code']==200){
              $private=$data['data']['wallet']['private'];
              $public=$data['data']['wallet']['public'];
		      $user_coin_id = $this->common_model->addRecords('coin_address_info',array(
		                                'user_id' => $USERid,                                
		                                'private' => $private,
		                                'public'  => $public,
		                                'original_address' => $public,                                
		                                'create_date' => date('Y-m-d H:i:s'),
		                                'update_date' => date('Y-m-d H:i:s')));
		            }
		          
		      
			$this->session->set_flashdata('success','Issuer Added Successfully');
			redirect('admin/add_issuer');
			 
		}
		$adminid = $this->session->userdata('login_id');
		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));
		$q = " SELECT unread_notification_count($adminid) as count";
		$data['notifi_count'] = $this->common_model->getRowByQuery($q);	

		$query="SELECT id as notification_id, getnotificationtype(notification_type_id) as type,link, recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,isread,get_duration(datetime) as duration FROM notification where recipient_id=$adminid and isread=0";
    	$data['notifi_list']= $this->common_model->getArrayByQuery($query);
    	$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
		$this->load->view('admin/add_issuer',$data);
	}

	public function issuer_verify($id)
	{
		$id = base64_decode($id);
		$this->common_model->updateRecords("users",array('kyc_status'=>1),array('id'=>$id));
		$recdata=file_get_contents('http://espsofttech.in/TokenAPI/api/redblock_token/create_wallet');
              $data=json_decode($recdata,true);
               if($data['code']==200){
              $private=$data['data']['wallet']['private'];
              $public=$data['data']['wallet']['public'];
		                  $user_coin_id = $this->common_model->addRecords('coin_address_info',array(
		                                'user_id' => $id,                                
		                                'private' => $private,
		                                'public'  => $public,
		                                'original_address' => $public,                                
		                                'create_date' => date('Y-m-d H:i:s'),
		                                'update_date' => date('Y-m-d H:i:s')));
		            }
		          

		       $title=" Profile Approved By admin";
			    $message="Your Profile was approved by admin";
			    $dataArray = array('notification_type_id'=>5,'recipient_id'=>$id,'sender_id'=>1,'title'=>$title,'message'=>$message,'link'=>'companyprofile');
			    $this->common_model->addRecords("notification", $dataArray);
		$this->session->set_flashdata('success','Issuer Verify Successfully');
		redirect('admin/issuer_profile/'.base64_encode($id));

	}
	public function investor_verify($id)
	{
		$id = base64_decode($id);
		$this->common_model->updateRecords("users",array('kyc_status'=>1),array('id'=>$id));		
		       $title=" Investor Profile Approved";
			    $message="Your Profile was approved by admin";
			    $dataArray = array('notification_type_id'=>6,'recipient_id'=>$id,'sender_id'=>1,'title'=>$title,'message'=>$message,'link'=>'investor');
			    $this->common_model->addRecords("notification", $dataArray);
		$this->session->set_flashdata('success','Investor Verify Successfully');
		redirect('admin/investor_profile/'.base64_encode($id));

	}
	public function chart()
	{
		$this->load->view('admin/chart');
	}

	public function update_issuer($id)
	{
		$id = base64_decode($id);
		if(isset($_POST['update_issuer']))
		{
			$data = $this->input->post();
			$dataArray = array(
								'companyname' => $data['company_name'],
								'fname' => $data['fname'],
								'lname' => $data['lname'],
								'email' => $data['email'],
								'mobile' => $data['phoneno'],
								'city' => $data['address']
								
									);
			$issuer_id = $this->common_model->updateRecords("users",$dataArray,array('id'=>$id));
			$this->session->set_flashdata('success','Issuer Update Successfully');
			redirect('admin/issuer_manage/'.base64_encode($id));

		}
		$adminid = $this->session->userdata('login_id');
		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));
		$data['issuer_data'] = $this->common_model->getSingleRecordById("users",array('id'=>$id));
		$q = " SELECT unread_notification_count($adminid) as count";
		$data['notifi_count'] = $this->common_model->getRowByQuery($q);	

		$query="SELECT id as notification_id, getnotificationtype(notification_type_id) as type,link, recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,isread,get_duration(datetime) as duration FROM notification where recipient_id=$adminid and isread=0";
    	$data['notifi_list']= $this->common_model->getArrayByQuery($query);
    	$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
		$this->load->view('admin/edit_issuer',$data);
	}
	public function contact_request()
	{		
		
		$adminid = $this->session->userdata('login_id');
		$que = "SELECT * from contact_us ORDER by id desc";
		$data['contact_data'] = $this->common_model->getArrayByQuery($que);
		
		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));		
		$q = " SELECT unread_notification_count($adminid) as count";
		$data['notifi_count'] = $this->common_model->getRowByQuery($q);	

		$query="SELECT id as notification_id, getnotificationtype(notification_type_id) as type,link, recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,isread,get_duration(datetime) as duration FROM notification where recipient_id=$adminid and isread=0";
    	$data['notifi_list']= $this->common_model->getArrayByQuery($query);
    	$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());

		$this->load->view('admin/contact_request_list',$data);
	}

	public function delete_issuer($id)
	{
		$id = base64_decode($id);
		$data = $this->common_model->getSingleRecordById("asset",array('issuer_id'=>$id));		
		if(empty($data))
		{
			$this->common_model->deleteRecords("officerdetail",array('issuer_id'=>$id));
			$this->common_model->deleteRecords("investor_kyc",array('user_id'=>$id));
			$this->common_model->deleteRecords("notification",array('recipient_id'=>$id));
			$this->common_model->deleteRecords("notification",array('sender_id'=>$id));
			$this->common_model->deleteRecords("bank",array('user_id'=>$id));	
			$this->common_model->deleteRecords("company_profile",array('userid'=>$id));
			$this->common_model->deleteRecords("users",array('id'=>$id));		
			$this->session->set_flashdata('success','Issuer info  Deleted Successfully');
			redirect('admin/issuer_manage');
		}
		else
		{
			$this->session->set_flashdata('error',"Asset Created issuer can't delete");
			redirect('admin/issuer_manage');
		}
		
	}

	public function issuer_manage()
	{
		$adminid = $this->session->userdata('login_id');
		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));
		$data['issuerInfo'] = $this->common_model->getAllRecordsById("users",array('usertypeid'=>2));	
		$q = " SELECT unread_notification_count($adminid) as count";
		$data['notifi_count'] = $this->common_model->getRowByQuery($q);	

		$query="SELECT id as notification_id, getnotificationtype(notification_type_id) as type,link, recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,isread,get_duration(datetime) as duration FROM notification where recipient_id=$adminid and isread=0";
    	$data['notifi_list']= $this->common_model->getArrayByQuery($query);
    	$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());	
		$this->load->view('admin/issuer_list',$data);
	}
	public function issuer_profile_list()
	{
		$adminid = $this->session->userdata('login_id');
		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));
		$q = " SELECT unread_notification_count($adminid) as count";
		$data['notifi_count'] = $this->common_model->getRowByQuery($q);	

		$query="SELECT id as notification_id, getnotificationtype(notification_type_id) as type,link, recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,isread,get_duration(datetime) as duration FROM notification where recipient_id=$adminid and isread=0";
    	$data['notifi_list']= $this->common_model->getArrayByQuery($query);
		// $data['issuer_data'] = $this->common_model->getAllRecordsById("users",array('usertypeid'=>2));
		$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
		$this->load->view('admin/issuer_profile_list',$data);
	}
	public function issuer_profile($id)
	{
		$id = base64_decode($id);
		$where =" where a.issuer_id=$id order by a.id desc";
		$whr=" where cp.userid=$id";
      	$data['assets_data']= $this->common_model->getAssetList($where);    
      	$data['profile_data']= $this->common_model->getCompanyProfile($whr);
		$data['issuer_profile'] = $this->common_model->getSingleRecordById("users",array('id'=>$id));	
		$data['id'] =$id;	
		$adminid = $this->session->userdata('login_id');
		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));
		$q = " SELECT unread_notification_count($adminid) as count";
		$data['notifi_count'] = $this->common_model->getRowByQuery($q);	

		$query="SELECT id as notification_id, getnotificationtype(notification_type_id) as type,link, recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,isread,get_duration(datetime) as duration FROM notification where recipient_id=$adminid and isread=0";
    	$data['notifi_list']= $this->common_model->getArrayByQuery($query);
    	$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
		$this->load->view('admin/issuer_profile',$data);
	}
	
	public function officer_profile($id)
	{

		$id = base64_decode($id);
		$adminid = $this->session->userdata('login_id');
		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));
		$data['officerdoc']=$this->common_model->getSingleRecordById("officerdetail",array('id'=>$id));
		$query =" SELECT officerdetail.*,users.companyname from officerdetail left join users on officerdetail.issuer_id = users.id where officerdetail.id =$id";
		$data['officerprofile'] = $this->common_model->getRowByQuery($query);	
		$q = " SELECT unread_notification_count($adminid) as count";
		$data['notifi_count'] = $this->common_model->getRowByQuery($q);	

		$query="SELECT id as notification_id, getnotificationtype(notification_type_id) as type,link, recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,isread,get_duration(datetime) as duration FROM notification where recipient_id=$adminid and isread=0";
    	$data['notifi_list']= $this->common_model->getArrayByQuery($query);	
    	$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
		$this->load->view('admin/officer_profile',$data);
	}

	public function officer_profile_list($id)
	{		
		$id = base64_decode($id);
		$adminid = $this->session->userdata('login_id');
		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));
		$query = "SELECT id,concat(first_name,' ',last_name) as officername ,image,designation from officerdetail where issuer_id=$id";
		$data['officer_profile'] = $this->common_model->getArrayByQuery($query);
		$data['id']=$id;
		$q = " SELECT unread_notification_count($adminid) as count";
		$data['notifi_count'] = $this->common_model->getRowByQuery($q);	

		$query="SELECT id as notification_id, getnotificationtype(notification_type_id) as type,link, recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,isread,get_duration(datetime) as duration FROM notification where recipient_id=$adminid and isread=0";
    	$data['notifi_list']= $this->common_model->getArrayByQuery($query);
    	$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
		$this->load->view('admin/officer_profile_list',$data);
	}
	public function title_management()
	{
		if(isset($_POST['update_logo']))
		{
			$data = $this->input->post();

                if($_FILES['image']['name']){
                    $imagename  = time().$_FILES['image']['name'];
                    $tmpname    = $_FILES['image']['tmp_name'];
                    $image      = base_url().'uploads/'.  $imagename;
                    move_uploaded_file($tmpname, 'uploads/'.$imagename);                      
                }else{
                    $image = $this->input->post("old_image");
                }
                if($_FILES['image1']['name']){
                    $imagename  = time().$_FILES['image1']['name'];
                    $tmpname    = $_FILES['image1']['tmp_name'];
                    $image1      = base_url().'uploads/'.  $imagename;
                    move_uploaded_file($tmpname, 'uploads/'.$imagename);                      
                }else{
                    $image1 = $this->input->post("old_image1");
                }
            $dataArray = array(
            				'title'=>$data['title'],
            				'logo' => $image,
            				'fevicon'=>$image1
            					);
            $logo_detail = $this->common_model->getSingleRecordById("tbl_title_logo",array());
            if(!empty($logo_detail))
            {
            	$id = $this->common_model->updateRecords("tbl_title_logo",$dataArray,array('id'=>$logo_detail['id']));            	
            }
            else
            {
            	$id = $this->common_model->addRecords("tbl_title_logo",$dataArray);            	
            }
            $this->session->set_flashdata('success','Record update Successfully');
		}

		$adminid = $this->session->userdata('login_id');
		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));
		$q = " SELECT unread_notification_count($adminid) as count";
		$data['notifi_count'] = $this->common_model->getRowByQuery($q);	

		$query="SELECT id as notification_id, getnotificationtype(notification_type_id) as type,link, recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,isread,get_duration(datetime) as duration FROM notification where recipient_id=$adminid and isread=0";
    	$data['notifi_list']= $this->common_model->getArrayByQuery($query);
    	$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
    	$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
		$this->load->view('admin/title&logo',$data);
	}
	public function add_officer($id='')
	{
		$id = base64_decode($id);
		if(isset($_POST['addOfficer']))
		{
			$data = $this->input->post();

                if($_FILES['profile_pic']['name']){
                    $imagename  = time().$_FILES['profile_pic']['name'];
                    $tmpname    = $_FILES['profile_pic']['tmp_name'];
                    $profile_pic      = base_url().'uploads/'.  $imagename;
                    move_uploaded_file($tmpname, 'uploads/'.$imagename);                      
                }else{
                    $profile_pic = $this->input->post("profile_pic");
                }

			$dataArray = array(
								'issuer_id'		 	=> $data['issuer_name'],
								'first_name' 	 	=> $data['first_name'],
								'last_name' 	 	=> $data['last_name'],
								'email' 		 	=> $data['email'],
								'phone'			 	=> $data['phone_number'],
								'dob' 			 	=> $data['dob'],
								'designation'	 	=> $data['designation'],
								'address'		 	=> $data['address'],
								'apartment_no'	 	=> $data['appartment'],
								'country_id'	 	=> $data['country'],
								'state_id'		 	=> $data['state'],
								'city'			 	=> $data['city'],
								'zipcode'		 	=> $data['zip_code'],								
								'biography' 	 	=> $data['biography'],								
								'image'  			=>$profile_pic,
								'created'			=>date('Y-m-d')

							);
			$oid = $this->common_model->addRecords("officerdetail",$dataArray);
			$this->session->set_flashdata('success','Officer Added Successfully');
		}
		
		$issuerQuery ="SELECT id,companyname from users where usertypeid=2";
		$data['issuer_name'] = $this->common_model->getArrayByQuery($issuerQuery);
		$query = "SELECT distinct c.id as country_id,c.name,c.currency,c.code from country as c inner join state as s on s.countryid=c.id order by c.name";
		$data['country_list'] =$this->common_model->getArrayByQuery($query);
		$query = "SELECT distinct c.id as country_id,c.name,c.currency,c.code from country as c inner join state as s on s.countryid=c.id order by c.name";
		$data['country'] =$this->common_model->getArrayByQuery($query);
		$data['id'] = $id;
		$adminid = $this->session->userdata('login_id');
		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));
		$q = " SELECT unread_notification_count($adminid) as count";
		$data['notifi_count'] = $this->common_model->getRowByQuery($q);	

		$query="SELECT id as notification_id, getnotificationtype(notification_type_id) as type,link, recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,isread,get_duration(datetime) as duration FROM notification where recipient_id=$adminid and isread=0";
    	$data['notifi_list']= $this->common_model->getArrayByQuery($query);
    	$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
		$this->load->view('admin/add_new_officer',$data);
	}

	public function getstate()
	{
		$cid = $this->input->post('get_state_list');		
		$squery = " SELECT * from state WHERE countryid =$cid";		
  		$stateArray =$this->common_model->getArrayByQuery($squery);  		
  		echo json_encode($stateArray);
	}

	public function officer_manage()
	{			
		$query =" SELECT officerdetail.*,users.companyname from officerdetail left join users on officerdetail.issuer_id = users.id order by officerdetail.id desc";
		$data['officerInfo'] = $this->common_model->getArrayByQuery($query);
		$adminid = $this->session->userdata('login_id');
		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));
		$q = " SELECT unread_notification_count($adminid) as count";
		$data['notifi_count'] = $this->common_model->getRowByQuery($q);	

		$query="SELECT id as notification_id, getnotificationtype(notification_type_id) as type,link, recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,isread,get_duration(datetime) as duration FROM notification where recipient_id=$adminid and isread=0";
    	$data['notifi_list']= $this->common_model->getArrayByQuery($query);
    	$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
		$this->load->view('admin/officer_list',$data);
	}

	public function update_officer($id)
	{
		$id = base64_decode($id);
		if(isset($_POST['updateOfficer']))
		{
			$data = $this->input->post();

				
                if($_FILES['profile_pic']['name']){
                    $imagename  = time().$_FILES['profile_pic']['name'];
                    $tmpname    = $_FILES['profile_pic']['tmp_name'];
                    $profile_pic      = base_url().'uploads/'.  $imagename;
                    move_uploaded_file($tmpname, 'uploads/'.$imagename);                      
                }else{
                    $profile_pic = $this->input->post("old_profile_pic");
                }

			$dataArray = array(
								'issuer_id'		 	=> $data['issuer_name'],
								'first_name' 	 	=> $data['first_name'],
								'last_name' 	 	=> $data['last_name'],
								'email' 		 	=> $data['email'],
								'phone'			 	=> $data['phone_number'],
								'dob' 			 	=> $data['dob'],
								'designation'	 	=> $data['designation'],
								'address'		 	=> $data['address'],
								'apartment_no'	 	=> $data['appartment'],
								'country_id'	 	=> $data['country'],
								'state_id'		 	=> $data['state'],
								'city'			 	=> $data['city'],
								'zipcode'		 	=> $data['zip_code'],								
								'biography' 	 	=> $data['biography'],								
								'image'  			=>$profile_pic

							);
			$oid = $this->common_model->updateRecords("officerdetail",$dataArray,array('id'=>$id));
			$this->session->set_flashdata('success','Officer Update Successfully');
			redirect('admin/officer_manage');
		}		
		$data['officerData'] = $this->common_model->getSingleRecordById("officerdetail",array('id'=>$id));		
		$issuerQuery ="SELECT id,companyname from users where usertypeid=2";
		$data['issuer_name'] = $this->common_model->getArrayByQuery($issuerQuery);
		$query = "SELECT distinct c.id as country_id,c.name,c.currency,c.code from country as c inner join state as s on s.countryid=c.id order by c.name";
		$data['country_list'] =$this->common_model->getArrayByQuery($query);
		$query = "SELECT distinct c.id as country_id,c.name,c.currency,c.code from country as c inner join state as s on s.countryid=c.id order by c.name";
		$data['country'] =$this->common_model->getArrayByQuery($query);
		$adminid = $this->session->userdata('login_id');
		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));
		$q = " SELECT unread_notification_count($adminid) as count";
		$data['notifi_count'] = $this->common_model->getRowByQuery($q);	

		$query="SELECT id as notification_id, getnotificationtype(notification_type_id) as type,link, recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,isread,get_duration(datetime) as duration FROM notification where recipient_id=$adminid and isread=0";
    	$data['notifi_list']= $this->common_model->getArrayByQuery($query);
    	$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
		$this->load->view('admin/edit_officer',$data);
	}

	public function officer_delete($id)
	{
		$id =base64_decode($id);
		$this->common_model->deleteRecords("officerdetail",array('id'=>$id));
		$this->session->set_flashdata('success','Officer Deleted');
		redirect('admin/officer_manage');

	}

	public function add_investor()
	{
		if(isset($_POST['add_investor']))
		{
			$data = $this->input->post();
			$password = $data['password'];
			$con_password = $data['con_password'];
			if($password!=$con_password)
			{
				$this->session->set_flashdata('error','Password And confirm password does not matched');
				redirect('admin/add_investor');
			}
			$dataArray = array(								
								'fname' => $data['fname'],
								'lname' => $data['lname'],
								'email' => $data['email'],
								'mobile' => $data['phoneno'],
								'city' => $data['address'],
								'usertypeid' =>3,
								'password' => md5($data['password'])
									);
			$id = $this->common_model->addRecords("users",$dataArray);
			$recdata=file_get_contents('http://espsofttech.in/TokenAPI/api/redblock_token/create_wallet');
              $data=json_decode($recdata,true);
               if($data['code']==200){

              $private=$data['data']['wallet']['private'];
              $public=$data['data']['wallet']['public'];
              
		                  $user_coin_id = $this->common_model->addRecords('coin_address_info',array(
		                                'user_id' => $id,                                
		                                'private' => $private,
		                                'public'  => $public,
		                                'original_address' => $public,                                
		                                'create_date' => date('Y-m-d H:i:s'),
		                                'update_date' => date('Y-m-d H:i:s')));
		            }
		         
		    
			$this->session->set_flashdata('success','Investor Added Successfully');
			redirect('admin/add_investor');
			 
		}
		$adminid = $this->session->userdata('login_id');
		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));
		$q = " SELECT unread_notification_count($adminid) as count";
		$data['notifi_count'] = $this->common_model->getRowByQuery($q);	

		$query="SELECT id as notification_id, getnotificationtype(notification_type_id) as type,link, recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,isread,get_duration(datetime) as duration FROM notification where recipient_id=$adminid and isread=0";
    	$data['notifi_list']= $this->common_model->getArrayByQuery($query);
    	$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
		$this->load->view('admin/add_investor',$data);
	}

	public function update_investor($id)
	{
		$id = base64_decode($id);
		if(isset($_POST['update_investor']))
		{
			$data = $this->input->post();
			$dataArray = array(								
								'fname' => $data['fname'],
								'lname' => $data['lname'],
								'email' => $data['email'],
								'mobile' => $data['phoneno'],
								'city' => $data['address']
									);
			$issuer_id = $this->common_model->updateRecords("users",$dataArray,array('id'=>$id));
			$this->session->set_flashdata('success','Investor Update Successfully');
			redirect('admin/investor_manage/'.base64_encode($id));

		}
		$data['investor_data'] = $this->common_model->getSingleRecordById("users",array('id'=>$id));
		$adminid = $this->session->userdata('login_id');
		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));
		$q = " SELECT unread_notification_count($adminid) as count";
		$data['notifi_count'] = $this->common_model->getRowByQuery($q);	

		$query="SELECT id as notification_id, getnotificationtype(notification_type_id) as type,link, recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,isread,get_duration(datetime) as duration FROM notification where recipient_id=$adminid and isread=0";
    	$data['notifi_list']= $this->common_model->getArrayByQuery($query);
    	$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
		$this->load->view('admin/edit_investor',$data);
	}
	public function delete_investor($id)
	{
		$id = base64_decode($id);	

		$check=$this->common_model->getSingleRecordById("investment",array('user_id'=>$id));
		if(empty($check)){
			$this->session->set_flashdata('success','Investor invested in asset, you can not delete !!!');
			redirect('admin/investor_manage');
		}

		$this->common_model->deleteRecords("bank",array('user_id'=>$id));	
		$this->common_model->deleteRecords("company_profile",array('userid'=>$id));	
		$this->common_model->deleteRecords("users",array('id'=>$id));

		$this->session->set_flashdata('success','Investor info  Deleted Successfully');
		redirect('admin/investor_manage');
	}
	public function investor_manage()
	{	
		$query="select u.* from users as u 
left join (select user_id,max(datetime) as datetime from investment group by user_id) as i on i.user_id=u.id
where u.usertypeid=3 order by i.datetime desc";
		$data['investorInfo'] = $this->common_model->getArrayByQuery($query);
		$adminid = $this->session->userdata('login_id');
		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));	
		$q = " SELECT unread_notification_count($adminid) as count";
		$data['notifi_count'] = $this->common_model->getRowByQuery($q);	

		$query="SELECT id as notification_id, getnotificationtype(notification_type_id) as type,link, recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,isread,get_duration(datetime) as duration FROM notification where recipient_id=$adminid and isread=0";
    	$data['notifi_list']= $this->common_model->getArrayByQuery($query);	
    	$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
		$this->load->view('admin/investor_list',$data);
	}
	public function transaction_request()
	{	
		if(isset($_POST['search_data']))
		{
			$where ="1";
			if(!empty($_POST['companyname']))
			{
				$companyname = $_POST['companyname'];
				$where .=" AND s.user_id='$companyname'";
				
			}
			if(!empty($_POST['assetname']))
			{
				$assetname = $_POST['assetname'];
				$where .=" AND s.asset_id='$assetname'";
			}
			if(!empty($_POST['statusname']))
			{
				$statusname = $_POST['statusname'];
				if($statusname==3)
				{
					$statusname=0;
				}
				$where .=" AND s.approve_status='$statusname'";				
			}

			$que = "SELECT s.id ,i.name as asset_name,u.companyname,concat(u.fname,' ',u.lname) as username,s.hash,s.amount, to_wallet,case when approve_status=2 then 'Rejected' when approve_status=1  then 'Approved' else 'Pending' end as approval_status, coalesce(approve_date,'') as status_date from send_token_request as s left join issuance as i on i.asset_id=s.asset_id left join users as u on u.id=s.user_id where $where order by s.id desc";
			$data['trans_data'] = $this->common_model->getArrayByQuery($que);

		}
		else
		{
			$que = "SELECT s.id ,i.name as asset_name,u.companyname,concat(u.fname,' ',u.lname) as username,s.hash,s.amount, to_wallet,case when approve_status=2 then 'Rejected' when approve_status=1  then 'Approved' else 'Pending' end as approval_status, coalesce(approve_date,'') as status_date from send_token_request as s left join issuance as i on i.asset_id=s.asset_id left join users as u on u.id=s.user_id  order by s.id desc";
			$data['trans_data'] = $this->common_model->getArrayByQuery($que);
		}

		$data['investorInfo'] = $this->common_model->getAllRecordsById("users",array('usertypeid'=>3));
		$adminid = $this->session->userdata('login_id');
		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));	
		$q = " SELECT unread_notification_count($adminid) as count";
		$data['notifi_count'] = $this->common_model->getRowByQuery($q);	

		$query="SELECT id as notification_id, getnotificationtype(notification_type_id) as type,link, recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,isread,get_duration(datetime) as duration FROM notification where recipient_id=$adminid and isread=0";
    	$data['notifi_list']= $this->common_model->getArrayByQuery($query);	

    	$quer = "SELECT users.id, users.companyname from send_token_request left join users on send_token_request.user_id=users.id";
    	$data['companydata'] = $this->common_model->getArrayByQuery($quer);
    	$quer = "SELECT asset.id, asset.name from send_token_request left join asset on send_token_request.asset_id=asset.id";
    	$data['assetdata'] = $this->common_model->getArrayByQuery($quer);

    	$quer = "SELECT approve_status as id, case when approve_status=2 then 'Rejected' when approve_status=1  then 'Approved' else 'Pending' end as approval_status from send_token_request ";
    	$data['statusdata'] = $this->common_model->getArrayByQuery($quer);
    	$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
		$this->load->view('admin/investor_trans_list',$data);
	}

	public function invest_trans_approve($id)
	{
		$id=base64_decode($id);
		$q ="SELECT asset_id,user_id,to_wallet,amount from send_token_request where id=$id";
		$iddata = $this->common_model->getRowByQuery($q);
		$asset_id=$iddata['asset_id'];
		$user_id=$iddata['user_id'];
		$to_wallet =$iddata['to_wallet'];
		$amount =$iddata['amount'];

		$investor_data =$this->common_model->getSingleRecordById("coin_address_info",array('user_id'=>$user_id));
		$asset_data = $this->common_model->getSingleRecordById("token_detail",array('asset_id'=>$asset_id));
		$coinurl = '13.233.136.121:7001/api/erc/transfer';   		          
		            $coinTransfer = array(
		                "from_address"      => $investor_data['public'],
		                "from_private_key"  => $investor_data['private'],
		                "contract_address"  => $asset_data['contract_address'],	
		                "to_address"        => $to_wallet,		                
		                "value"             => $amount,
		            );  
		    $coin_data_string = json_encode($coinTransfer);            
	        $response = $this->common_model->curl_url_post($coinurl,$coin_data_string);
	        if(@$response->hash)
	        {
				$d = $this->common_model->updateRecords("send_token_request",array('hash'=>$response->hash,'approve_status'=>1,'approve_date'=>date('Y-m-d H:i:s')),array('id'=>$id));
				$this->session->set_flashdata('success','Request Approved successfully');
				redirect('admin/transaction_request');
			}
			else
			{
				$this->session->set_flashdata('error','Some Error occur, please try again');
				redirect('admin/transaction_request');
			}

	}
	public function invest_trans_reject($id)
	{
		$id=base64_decode($id);
		$d = $this->common_model->updateRecords("send_token_request",array('approve_status'=>2,'approve_date'=>date('Y-m-d H:i:s')),array('id'=>$id));
		$this->session->set_flashdata('success','Request Reject successfully');
		redirect('admin/transaction_request');

	}
	public function investor_profile($id)
	{
		$id = base64_decode($id);
		$where =" where investment.user_id=$id order by investment.id desc";
		$data['assets_data']= $this->common_model->getInvestAssetList($where);		
		$data['investor_profile'] = $this->common_model->getSingleRecordById("users",array('id'=>$id));	
		$adminid = $this->session->userdata('login_id');
		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));	
		$q = " SELECT unread_notification_count($adminid) as count";
		$data['notifi_count'] = $this->common_model->getRowByQuery($q);	

		$query="SELECT id as notification_id, getnotificationtype(notification_type_id) as type,link, recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,isread,get_duration(datetime) as duration FROM notification where recipient_id=$adminid and isread=0";
    	$data['notifi_list']= $this->common_model->getArrayByQuery($query);
    	$data['id']=$id;
    	$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
		$this->load->view('admin/investor_profile',$data);
	}
	public function approve_investment($id,$uid)
	{
		$id=base64_decode($id);
		$iid =base64_decode($uid);
		$q ="SELECT asset_id,investment_amount from investment where id=$id";
		$iddata = $this->common_model->getRowByQuery($q);
		$asset_id=$iddata['asset_id'];
		$amount=$iddata['investment_amount'];
		$q ="SELECT issuer_id from asset where id=$asset_id";
		$aiddata = $this->common_model->getRowByQuery($q);
		$issuer_id=$aiddata['issuer_id'];
		$issuer_data =$this->common_model->getSingleRecordById("coin_address_info",array('user_id'=>$issuer_id));
		$investor_data =$this->common_model->getSingleRecordById("coin_address_info",array('user_id'=>$iid));
		$asset_data = $this->common_model->getSingleRecordById("token_detail",array('asset_id'=>$asset_id));
		// $uid = base64_decode($uid);
			//APPROVE TOKEN TRANSFER FROM ASSET
		$coinurl = '52.66.202.69:7000/api/erc/approveTransfer';   		          
		            $coinTransfer = array(
		                "from_address"      => "0xFa8e543529D7c46EE03D1953c6219b2583715355",
		                "from_private_key"  => "0xcc7bedc4c3872d7cf3ebb15ec4c24556d8b2622efa819eba5f7f84c1af602f69",
		                "contract_address"  => $asset_data['contract_address']
		            );  
		    $coin_data_string = json_encode($coinTransfer);            
	        $response = $this->common_model->curl_url_post($coinurl,$coin_data_string);

	        //////////////////////////////////////////////////////////
				$coinurl = '52.66.202.69:7000/api/erc/transfer';   		          
		            $coinTransfer = array(
		                "from_address"      => $issuer_data['public'],
		                "from_private_key"  => $issuer_data['private'],
		                "contract_address"  => $asset_data['contract_address'],	
		                "to_address"        => $investor_data['public'],		                
		                "value"             => $amount
		            );  
		    $coin_data_string = json_encode($coinTransfer);            
	        $response = $this->common_model->curl_url_post($coinurl,$coin_data_string);
	        if(@$response->hash)
	        {
				$this->common_model->updateRecords("investment",array('approve_status'=>1,'approve_date'=>date('Y-m-d H:i:s'),'hash'=>$response->hash),array('id'=>$id));
				$this->session->set_flashdata('success','Investment Approved successfully');
				redirect('admin/investor_profile/'.$uid);
			}
			else
			{
				$this->session->set_flashdata('error','Some Error occur, please try again');
				redirect('admin/investor_profile/'.$uid);
			}
	}
	public function reject_investment($id,$uid)
	{
		$id=base64_decode($id);
		// $uid = base64_decode($uid);
		$this->common_model->updateRecords("investment",array('approve_status'=>2,'approve_date'=>date('Y-m-d H:i:s')),array('id'=>$id));
		$this->session->set_flashdata('success','Investment rejected successfully');
		redirect('admin/investor_profile/'.$uid);
	}
	public function investor_profile_list()
	{		
		$adminid = $this->session->userdata('login_id');
		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));
		$q = " SELECT unread_notification_count($adminid) as count";
		$data['notifi_count'] = $this->common_model->getRowByQuery($q);	

		$query="SELECT id as notification_id, getnotificationtype(notification_type_id) as type,link, recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,isread,get_duration(datetime) as duration FROM notification where recipient_id=$adminid and isread=0";
    	$data['notifi_list']= $this->common_model->getArrayByQuery($query);
    	$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
		$this->load->view('admin/investor_profile_list',$data);
	}
	public function assets_detail($id)	{
		
		$adminid = $this->session->userdata('login_id');
		$asset_id=base64_decode($id);
          $query="SELECT i.id,getassetissuer(i.asset_id) as issuer_name,concat(u.fname,' ',u.lname) as investor_name,a.name as asset_name,wallet_address(i.user_id) as wallet_address,get_user_email(i.user_id) as email, date_format(i.datetime,'%d %M %Y') as invest_date,date_format(i.datetime,'%H:%i:%s') as invest_time,ic.startdate, ic.enddate, coalesce(i.investment_amount,'') as investment_amount, coalesce(ceil(i.investment_amount/price_per_unit),'') as units,i.hash from investment as i left join asset as a on a.id=i.asset_id left join issuance as ic on ic.asset_id=i.asset_id left join users as u on u.id=i.user_id where i.asset_id=$asset_id ORDER BY i.id desc limit 3";
         $data['transaction_data'] = $this->common_model->getArrayByQuery($query);      

         $quuery="SELECT 'Target' as label, target as value  from asset where id=$asset_id
		union ALL
		SELECT 'Fund Raised' as label, round(sum(COALESCE(investment_amount,0))) as value from investment where asset_id=$asset_id";
		$data['piechartData']= $this->common_model->getArrayByQuery($quuery);

         $querry ="SELECT       
			        concat(YEAR(datetime),'-',MONTH(datetime),'-','01') AS x,
			        round(SUM(COALESCE(investment_amount,0))) AS y
			      FROM investment 
			      GROUP BY
			    	concat(YEAR(datetime),'-',MONTH(datetime),'-','01')";
		$data['chart_data'] = $this->common_model->getArrayByQuery($querry);	


    	$where=" where c.asset_id=$asset_id order by c.id desc limit 3";
      	$data['comment_data']= $this->common_model->get_comment($where);
      	
		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));
		$q = " SELECT unread_notification_count($adminid) as count";
		$data['notifi_count'] = $this->common_model->getRowByQuery($q);	

		$query="SELECT id as notification_id, getnotificationtype(notification_type_id) as type,link, recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,isread,get_duration(datetime) as duration FROM notification where recipient_id=$adminid and isread=0";
    	$data['notifi_list']= $this->common_model->getArrayByQuery($query);
    	$data['id']=$asset_id;
    	$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
		$this->load->view('admin/assets_detail',$data);
	}
	public function investor_whitelist($id)
	{
		$adminid = $this->session->userdata('login_id');

		$asset_id=base64_decode($id);
          $query="SELECT i.id,getassetissuer(i.asset_id) as issuer_name,concat(u.fname,' ',u.lname) as investor_name,a.name as asset_name,wallet_address(i.user_id) as wallet_address,get_user_email(i.user_id) as email, date_format(i.datetime,'%d %M %Y') as invest_date,date_format(i.datetime,'%H:%i:%s') as invest_time,ic.startdate, ic.enddate, sum(coalesce(i.investment_amount,'')) as investment_amount, coalesce(ceil(i.investment_amount/price_per_unit),'') as units,case when w.id is null then 0 else 1 end as is_whitelist,w.id as whitelist_id from investment as i left join asset as a on a.id=i.asset_id left join issuance as ic on ic.asset_id=i.asset_id left join users as u on u.id=i.user_id left join whitelist as w on w.investor_id=u.id and w.asset_id=$asset_id where i.asset_id=$asset_id";
         $data['invest_white_data'] = $this->common_model->getArrayByQuery($query);

		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));
		$q = " SELECT unread_notification_count($adminid) as count";
		$data['notifi_count'] = $this->common_model->getRowByQuery($q);	

		$query="SELECT id as notification_id, getnotificationtype(notification_type_id) as type,link, recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,isread,get_duration(datetime) as duration FROM notification where recipient_id=$adminid and isread=0";
    	$data['notifi_list']= $this->common_model->getArrayByQuery($query);
    	$data['id']=$asset_id;
    	$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
		$this->load->view('admin/investor_whitelist',$data);
	}

	public function investor_white_list($id)
	{
		$id = base64_decode($id);
		$query = "select 
					i.id as investment_id,
					td.contract_address,
					isu.private as issuer_private_key,
					isu.public as issuer_public_key,
					inv.public as investor_public_key,
					a.id  as asset_id,
					i.user_id as investor_id
					from investment as i 
					left join asset as a on a.id=i.asset_id
					left join token_detail as td on td.asset_id=a.id
					left join coin_address_info as isu on isu.user_id=a.issuer_id
					left join coin_address_info as inv on inv.user_id=i.user_id
					where i.id=$id";
		$data = $this->common_model->getRowByQuery($query);
		
		$apiArray = array(
								'from_address' => $data['issuer_public_key'],
								'from_private_key' => $data['issuer_private_key'],
								'whitelist_address' => $data['investor_public_key'],
								'contract_address' => $data['contract_address']								
								);			

			$url = "52.66.202.69:7000/api/erc/addWhitelist";
			$postData = json_encode($apiArray);
			$response = $this->common_model->curl_url_post($url,$postData);

			if(@$response->hash)
			{
				$addArr = array(
								'asset_id'=>$data['asset_id'],
								'investor_id'=>$data['investor_id'],
								'hash'=>$response->hash
								);
				$this->common_model->addRecords("whitelist",$addArr);
				$this->session->set_flashdata('success','Investor Whitelisted Successfully. Hash - '.$response->hash);
				redirect('admin/investor_whitelist/'.base64_encode($data['asset_id']));

			}
			else
			{
				$this->session->set_flashdata('error','Some error occure please try again later');
				redirect('admin/investor_whitelist/'.base64_encode($data['asset_id']));
			}
	}
	public function investor_black_list($id,$white_id)
	{
		$id = base64_decode($id);
		$white_id = base64_decode($white_id);

		$query = "select 
					i.id as investment_id,
					td.contract_address,
					isu.private as issuer_private_key,
					isu.public as issuer_public_key,
					inv.public as investor_public_key,
					a.id  as asset_id,
					i.user_id as investor_id
					from investment as i 
					left join asset as a on a.id=i.asset_id
					left join token_detail as td on td.asset_id=a.id
					left join coin_address_info as isu on isu.user_id=a.issuer_id
					left join coin_address_info as inv on inv.user_id=i.user_id
					where i.id=$id";
		$data = $this->common_model->getRowByQuery($query);
		
		$apiArray = array(
								'from_address' => $data['issuer_public_key'],
								'from_private_key' => $data['issuer_private_key'],
								'whitelist_address' => $data['investor_public_key'],
								'contract_address' => $data['contract_address']								
								);			

			$url = "52.66.202.69:7000/api/erc/removeWhitelist";
			$postData = json_encode($apiArray);
			$response = $this->common_model->curl_url_post($url,$postData);

			if(@$response->hash)
			{
				
				$this->common_model->deleteRecords("whitelist",array('id'=>$white_id));
				$this->session->set_flashdata('success','Investor Blacklisted Successfully. Hash - '.$response->hash);
				redirect('admin/investor_whitelist/'.base64_encode($data['asset_id']));

			}
			else
			{
				$this->session->set_flashdata('error','Some error occure please try again later');
				redirect('admin/investor_whitelist/'.base64_encode($data['asset_id']));
			}
	}
	public function documents($id)
	{
		$adminid = $this->session->userdata('login_id');

		$asset_id=base64_decode($id);          
           $query="SELECT 1 as id,'Company Documents'  as document_type,3 as files,min(update_date) as update_date, 'Folder' as type from company_profile where userid in (select issuer_id from asset where id=$asset_id) 
          union all 
          select 2 as id,'Issuance Documents'  as document_type,3 as files, min(update_date) as update_date, 'Folder' as type from issuance where asset_id=$asset_id
          union all 
          select 3 as id, 'Funding Documents' as document_type,3 as files,min(update_date) as update_date, 'Folder' as type from asset where id=$asset_id";
          $document_type = $this->common_model->getArrayByQuery($query);
            $document = array();
            foreach ($document_type as $value) {
              if($value['id']==1){
            $query="SELECT 1 as document_id, 'Incorporation Document' as name,incorporation_doc as doc_name,'Documents' as type,date_format(coalesce(update_date,datetime),'%d %M %y') as update_date from company_profile where userid in (select issuer_id from asset where id=$asset_id)
            union all 
            select 1 as document_id,'Bylaws' as name,bylaws_doc as doc_name,'Documents' as type,date_format(coalesce(update_date,datetime),'%d %M %y') as update_date from company_profile where userid in (select issuer_id from asset where id=$asset_id)
            union all 
            select 1 as document_id,'Organization Chart' as name, org_chart  as doc_name,'Documents' as type,date_format(coalesce(update_date,datetime),'%d %M %y') as update_date from company_profile where userid in (select issuer_id from asset where id=$asset_id)";
              }
              if($value['id']==2){
                $query="SELECT 2 as document_id,'Memorandum' as name,memorandum as doc_name,'Documents' as type,date_format(coalesce(update_date,datetime),'%d %M %y') as update_date from issuance where asset_id=$asset_id
            union all 
            select 2 as document_id,'Subscription Agreement' as name,subscription_agreement as doc_name,'Documents' as type,date_format(coalesce(update_date,datetime),'%d %M %y') as update_date from issuance where asset_id=$asset_id 
            union all 
            select 2 as document_id,'Token Agreement' as name,token_agreement as doc_name,'Documents' as type,date_format(coalesce(update_date,datetime),'%d %M %y') as update_date from issuance where asset_id=$asset_id";

          }
          if($value['id']==3){
            $query="SELECT 3 as document_id,'Funding Image' as name,imagefile as doc_name,'Documents' as type,date_format(coalesce(update_date,datetime),'%d %M %y') as update_date from asset where id =$asset_id
            union all 
            select 3 as document_id,'Funding Video' as name,vediofile as doc_name,'Documents' as type,date_format(coalesce(update_date,datetime),'%d %M %y') as update_date from asset where id =$asset_id
            union all 
            select 3 as document_id,'Pitch Deck' as name,pitchdeck as doc_name,'Documents' as type,date_format(coalesce(update_date,datetime),'%d %M %y') as update_date from asset where id =$asset_id";
            }
            $document =$this->common_model->getArrayByQuery($query);
            $recdata[] = array('document_type' => $value, 'document' => $document );

          }
          // echo "<pre>";
          // print_r($recdata);die();

          $data['recdata'] = $recdata;

		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));
		$q = " SELECT unread_notification_count($adminid) as count";
		$data['notifi_count'] = $this->common_model->getRowByQuery($q);	

		$query="SELECT id as notification_id, getnotificationtype(notification_type_id) as type,link, recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,isread,get_duration(datetime) as duration FROM notification where recipient_id=$adminid and isread=0";
    	$data['notifi_list']= $this->common_model->getArrayByQuery($query);
    	$data['id']=$asset_id;
    	$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
		$this->load->view('admin/investor_documents',$data);
	}
	public function transaction($id)
	{
		$adminid = $this->session->userdata('login_id');
		$asset_id=base64_decode($id);
          $query="SELECT i.id,getassetissuer(i.asset_id) as issuer_name,concat(u.fname,' ',u.lname) as investor_name,a.name as asset_name,wallet_address(i.user_id) as wallet_address,get_user_email(i.user_id) as email, date_format(i.datetime,'%d %M %Y') as invest_date,date_format(i.datetime,'%H:%i:%s') as invest_time,ic.startdate, ic.enddate, coalesce(i.investment_amount,'') as investment_amount, coalesce(ceil(i.investment_amount/price_per_unit),'') as units from investment as i left join asset as a on a.id=i.asset_id left join issuance as ic on ic.asset_id=i.asset_id left join users as u on u.id=i.user_id where i.asset_id=$asset_id";
         $data['transaction_data'] = $this->common_model->getArrayByQuery($query);


		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));
		$q = " SELECT unread_notification_count($adminid) as count";
		$data['notifi_count'] = $this->common_model->getRowByQuery($q);	

		$query="SELECT id as notification_id, getnotificationtype(notification_type_id) as type,link, recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,isread,get_duration(datetime) as duration FROM notification where recipient_id=$adminid and isread=0";
    	$data['notifi_list']= $this->common_model->getArrayByQuery($query);
    	$data['id']=$asset_id;
    	$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
		$this->load->view('admin/transaction_list',$data);
	}
	public function distribution($id)
	{
		$asset_id = base64_decode($id);
		$query = "SELECT distribution_master.id,asset.name as asset_name,distribution_master.amount,distribution_payment_method.name as payment_method,distribution_master.dividend_date,distribution_master.last_payment_date,distribution_frequency.name as distribution_frequency,distribution_master.approval_status,distribution_master.approval_date from distribution_master left JOIN asset on distribution_master.asset_id=asset.id LEFT JOIN distribution_payment_method on distribution_master.distribution_payment_method_id=distribution_payment_method.id left JOIN distribution_frequency on distribution_master.distribution_frequency_id=distribution_frequency.id where distribution_master.asset_id=$asset_id";
		$data['distribution_data'] = $this->common_model->getArrayByQuery($query);

		$adminid = $this->session->userdata('login_id');
		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));
		$q = " SELECT unread_notification_count($adminid) as count";
		$data['notifi_count'] = $this->common_model->getRowByQuery($q);	

		$query="SELECT id as notification_id, getnotificationtype(notification_type_id) as type,link, recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,isread,get_duration(datetime) as duration FROM notification where recipient_id=$adminid and isread=0";
    	$data['notifi_list']= $this->common_model->getArrayByQuery($query);
    	$data['id']=$asset_id;
    	$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
		$this->load->view('admin/distribution',$data);
	}
	public function distribution_approve($id,$aid)
	{
		$id=base64_decode($id);
		$dd = $this->common_model->updateRecords("distribution_master",array('approval_status'=>1,'approval_date'=>date('Y-m-d H:i:s')),array('id'=>$id));
		$this->session->set_flashdata('success','Distribution Approved successfully');
		redirect('admin/distribution/'.$aid);
	}
	public function distribution_reject($id,$aid)
	{
		$id=base64_decode($id);
		$dd = $this->common_model->updateRecords("distribution_master",array('approval_status'=>2,'approval_date'=>date('Y-m-d H:i:s')),array('id'=>$id));
		$this->session->set_flashdata('success','Distribution Reject successfully');
		redirect('admin/distribution/'.$aid);
	}
	public function issuance_detail($id)
	{
		$id = base64_decode($id);
		// echo $id;die();
		$query=" SELECT * from issuance where asset_id=$id";
      	$data['issuance_detail'] = $this->common_model->getRowByQuery($query);    
      	$adminid = $this->session->userdata('login_id');
		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));  
		$q = " SELECT unread_notification_count($adminid) as count";
		$data['notifi_count'] = $this->common_model->getRowByQuery($q);	

		$query="SELECT id as notification_id, getnotificationtype(notification_type_id) as type,link, recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,isread,get_duration(datetime) as duration FROM notification where recipient_id=$adminid and isread=0";
    	$data['notifi_list']= $this->common_model->getArrayByQuery($query);	
    	$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
		$this->load->view('admin/issuance_detail',$data);
	}
	

	public function update_issuance()
	{	


				if($_FILES['memorandum']['name']){
                    $imagename  = time().$_FILES['memorandum']['name'];
                    $tmpname    = $_FILES['memorandum']['tmp_name'];
                    $memorandum      = base_url().'uploads/'.  $imagename;
                    move_uploaded_file($tmpname, 'uploads/'.$imagename);                      
                }else{
                    $memorandum = $this->input->post("memorandum_old");
                }
                if($_FILES['subscription_agreement']['name']){
                    $imagename  = time().$_FILES['subscription_agreement']['name'];
                    $tmpname    = $_FILES['subscription_agreement']['tmp_name'];
                    $subscription_agreement      = base_url().'uploads/'.  $imagename;
                    move_uploaded_file($tmpname, 'uploads/'.$imagename);                      
                }else{
                    $subscription_agreement = $this->input->post("subscription_agreement_old");
                }
                if($_FILES['token_agreement']['name']){
                    $imagename  = time().$_FILES['token_agreement']['name'];
                    $tmpname    = $_FILES['token_agreement']['tmp_name'];
                    $token_agreement      = base_url().'uploads/'.  $imagename;
                    move_uploaded_file($tmpname, 'uploads/'.$imagename);                      
                }else{
                    $token_agreement = $this->input->post("token_agreement_old");
                }
		$id = $_POST['id'];

		$dataArray = array(
        'issuancetype_id'=>$_POST['issuancetype_id'],
        'unittype_id'=>$_POST['unittype_id'],
        'name'=>$_POST['name'],
        'symbol'=>$_POST['symbol'],
        'total_supply'=>$_POST['total_supply'],
        'price_per_unit'=>$_POST['price_per_unit'],
        'exemption_id'=>$_POST['exemption_id'],
        'is_email_ceremonial'=>$_POST['is_email_ceremonial'],
        'is_restriction'=>$_POST['is_restriction'],
        'is_gambling'=>$_POST['is_gambling'],
        'startdate'=>$_POST['startdate'],
        'enddate'=>$_POST['enddate'],
        'max_invest'=>$_POST['max_invest'],
        'allow_accredited'=>$_POST['allow_accredited'],
        'allow_nonaccredited'=>$_POST['allow_nonaccredited'],
        'allow_us'=>$_POST['allow_us'],
        'allow_nonus'=>$_POST['allow_nonus'],
        'roundtype'=>$_POST['roundtype'],
        'roundsize'=>$_POST['roundsize'],
        'raise_todate'=>$_POST['raise_todate'],
        'min_invest'=>$_POST['min_invest'],
        'min_target'=>$_POST['min_target'],
        'securitytype_id'=>$_POST['securitytype_id'],
        'share_price'=>$_POST['share_price'],
        'pre_valuation'=>$_POST['pre_valuation'],
        'option_pool'=>$_POST['option_pool'],
        'preference'=>$_POST['preference'],  
        'memorandum'=>$memorandum,
        'subscription_agreement'=>$subscription_agreement,
        'token_agreement'=>$token_agreement,           
        'update_date'=>date("Y-m-d H:i:s")
    );		

		$idd = $this->common_model->updateRecords("issuance",$dataArray,array('asset_id'=>$id));
		echo $idd;
	}
	public function assets_manage($id='')
	{
		
		$id = base64_decode($id);
		if(!empty($id))
		{
			$query = "SELECT a.id as asset_id,a.name, a.issuer_id,u.companyname, a.target,a.unitprice as price_per_unit,a.mininvest as min_invest,a.imagefile as image,a.vediofile as video,a.website,a.country_id,a.why_invest,a.overview,a.description,a.pitchdeck as pitch_deck,a.riskfactor as risk_factor,a.isapproved,issuance.startdate,issuance.enddate,issuance.max_invest,issuance.is_approved,issuance.symbol,issuance.total_supply,token_detail.contract_address,token_detail.hash from asset as a left join users as u on u.id=a.issuer_id left join issuance on a.id=issuance.asset_id left join token_detail on a.id=token_detail.asset_id where a.issuer_id=$id order by a.id DESC ";
			$data['assets_value'] = $this->common_model->getArrayByQuery($query);			
		}
		else
		{
			$query = "SELECT a.id as asset_id,a.name, a.issuer_id,u.companyname, a.target,a.unitprice as price_per_unit,a.mininvest as min_invest,a.imagefile as image,a.vediofile as video,a.website,a.country_id,a.why_invest,a.overview,a.description,a.pitchdeck as pitch_deck,a.riskfactor as risk_factor,a.isapproved,issuance.startdate,issuance.enddate,issuance.max_invest,issuance.is_approved,issuance.symbol,issuance.total_supply,token_detail.contract_address,token_detail.hash from asset as a left join users as u on u.id=a.issuer_id left join issuance on a.id=issuance.asset_id left join token_detail on a.id=token_detail.asset_id order by a.id DESC ";
			$data['assets_value'] = $this->common_model->getArrayByQuery($query);
		}
		
		
		$query = "SELECT id, case when companyname is not null then companyname else concat(fname,' ',lname) end as companyname from users where usertypeid=2";
		$data['companyname'] = $this->common_model->getArrayByQuery($query);
		$adminid = $this->session->userdata('login_id');
		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));
		$q = " SELECT unread_notification_count($adminid) as count";
		$data['notifi_count'] = $this->common_model->getRowByQuery($q);	

		$query="SELECT id as notification_id, getnotificationtype(notification_type_id) as type,link, recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,isread,get_duration(datetime) as duration FROM notification where recipient_id=$adminid and isread=0";
    	$data['notifi_list']= $this->common_model->getArrayByQuery($query);
    	$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
		$this->load->view('admin/assets_list',$data);
	}

	public function companydetail()
	{
		$issuerid = $this->input->post('issuer_name');
		redirect('admin/assets_manage/'.base64_encode($issuerid));
	}

	public function getcompandetail()
	{
		$comid = $_POST['comid'];
		$where =" where a.issuer_id=$comid order by a.id desc";		
      	$data['assets_data']= $this->common_model->getAssetList($where); 
      	print_r($data);

	}
	public function assets_view()
	{
		$adminid = $this->session->userdata('login_id');
		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));
		$q = " SELECT unread_notification_count($adminid) as count";
		$data['notifi_count'] = $this->common_model->getRowByQuery($q);	

		$query="SELECT id as notification_id, getnotificationtype(notification_type_id) as type,link, recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,isread,get_duration(datetime) as duration FROM notification where recipient_id=$adminid and isread=0";
    	$data['notifi_list']= $this->common_model->getArrayByQuery($query);
    	$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
		$this->load->view('admin/assets_view',$data);
	}

	public function assets_delete($id)
	{
		$id = base64_decode($id);
		$a = $this->common_model->deleteRecords("issuance",array('asset_id'=>$id));
		$d = $this->common_model->deleteRecords("asset",array('id'=>$id));
		$this->session->set_flashdata('success','Asset Deleted');
		redirect('admin/assets_manage');
	}

	public function add_new_assets($id='')
	{
		$id =base64_decode($id);
		if(isset($_POST['addAssets']))
		{
			$data = $this->input->post();

				if($_FILES['image']['name']){
                    $imagename  = time().$_FILES['image']['name'];
                    $tmpname    = $_FILES['image']['tmp_name'];
                    $image      = 'uploads/'.  $imagename;
                    move_uploaded_file($tmpname, 'uploads/'.$imagename);                      
                }else{
                    $image = $this->input->post("image");
                } 
                if($_FILES['video']['name']){
                    $imagename  = time().$_FILES['video']['name'];
                    $tmpname    = $_FILES['video']['tmp_name'];
                    $video      = 'uploads/'.  $imagename;
                    move_uploaded_file($tmpname, 'uploads/'.$imagename);                      
                }else{
                    $video = $this->input->post("video");
                }
                if($_FILES['pdf']['name']){
                    $imagename  = time().$_FILES['pdf']['name'];
                    $tmpname    = $_FILES['pdf']['tmp_name'];
                    $pdf      = 'uploads/'.  $imagename;
                    move_uploaded_file($tmpname, 'uploads/'.$imagename);                      
                }else{
                    $pdf = $this->input->post("pdf");
                }
			$dataArray = array(
								'issuer_id' 	=> $data['issuer_name'],	
								'name' 			=> $data['asset_name'],
								'target' 		=> $data['target_raise'],
								'unitprice' 	=> $data['price_unit'],
								'mininvest' 	=> $data['min_invest'],
								'website'		=> $data['asset_website'],
								'country_id' 	=> $data['country'],
								'why_invest' 	=> $data['why_invest'],
								'description' 	=> $data['description'],
								'riskfactor' 	=> $data['risk_factor'],
								'imagefile'		=> $image,
								'vediofile'		=> $video,
								'pitchdeck'		=> $pdf
								);
			$asset_id = $this->common_model->addRecords("asset",$dataArray);
			$issuanceArray = array('asset_id'=>$asset_id,'name'=>$data['asset_name'],'price_per_unit'=>$data['price_unit'],'min_invest'=>$data['min_invest'],'share_price'=>$data['price_unit']);
     		$newid2=$this->common_model->addRecords("issuance", $issuanceArray);     		
     		$this->session->set_flashdata('success','Assets Added Successfully');
		}
		$issuerQuery ="SELECT id,companyname from users where usertypeid=2";
		$data['issuer_name'] = $this->common_model->getArrayByQuery($issuerQuery);
		$query = "SELECT distinct c.id as country_id,c.name,c.currency,c.code from country as c inner join state as s on s.countryid=c.id order by c.name";
		$data['country_list'] =$this->common_model->getArrayByQuery($query);
		$data['id'] =$id;	
		$adminid = $this->session->userdata('login_id');
		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));	
		$q = " SELECT unread_notification_count($adminid) as count";
		$data['notifi_count'] = $this->common_model->getRowByQuery($q);	

		$query="SELECT id as notification_id, getnotificationtype(notification_type_id) as type,link, recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,isread,get_duration(datetime) as duration FROM notification where recipient_id=$adminid and isread=0";
    	$data['notifi_list']= $this->common_model->getArrayByQuery($query);
    	$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
		$this->load->view('admin/add_new_assets',$data);
	}
	public function investor_portfolio($id,$asset_id)
	{
		$id=base64_decode($id);
		$asset_id=base64_decode($asset_id);
		$where =" where a.id=$asset_id";
      	$data['portfolio_data']= $this->common_model->investor_portfolio_detail_admin($id,$where);
      	$quuery="SELECT 'Target' as label, target as value  from asset where id=$asset_id
		union ALL
		SELECT 'Fund Raised' as label, round(sum(COALESCE(investment_amount,0))) as value from investment where asset_id=$asset_id";
		$data['piechartData']= $this->common_model->getArrayByQuery($quuery);

      	$querry ="SELECT       
		       concat(YEAR(datetime),'-',MONTH(datetime),'-','01') AS x,
		       round(SUM(COALESCE(investment_amount,0))) AS y
		      FROM investment 
		      GROUP BY
		   	concat(YEAR(datetime),'-',MONTH(datetime),'-','01')";
		$data['chart_data'] = $this->common_model->getArrayByQuery($querry);	
      	
		$adminid = $this->session->userdata('login_id');
		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));
		$q = " SELECT unread_notification_count($adminid) as count";
		$data['notifi_count'] = $this->common_model->getRowByQuery($q);	

		$query="SELECT id as notification_id, getnotificationtype(notification_type_id) as type,link, recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,isread,get_duration(datetime) as duration FROM notification where recipient_id=$adminid and isread=0";
    	$data['notifi_list']= $this->common_model->getArrayByQuery($query);
    	$data['id'] = $id;
    	$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
		$this->load->view('admin/investor_portfolio',$data);
	}
	public function issuance_approve($id)
	{
		$aid= base64_decode($id);
		$aa = $this->common_model->getSingleRecordById("asset",array('id'=>$aid));
		if($aa['isapproved']==1)
		{
			$issuer_id = $aa['issuer_id'];		
			$adminid = $this->session->userdata('login_id');
			$adminwallet = $this->common_model->getSingleRecordById("coin_address_info",array('user_id'=>$adminid));
			$admin_address = $adminwallet['public'];
			$issuerwallet = $this->common_model->getSingleRecordById("coin_address_info",array('user_id'=>$issuer_id));
			$issuer_address = $issuerwallet['public'];
			$issuer_private = $issuerwallet['private'];
			$assetdata = $this->common_model->getSingleRecordById("issuance",array('asset_id'=>$aid));
			$name = $assetdata['name'];
			$symbol = $assetdata['symbol'];
			$total_supply = $assetdata['total_supply'];
			$apiArray = array(
								'issuer_address' => $issuer_address,
								'issuer_private_key' => $issuer_private,
								'token_name' => $name,
								'token_symbol' => $symbol,
								'admin_address' => $admin_address,
								'total_supply'=> $total_supply
								);
			

			$url = "52.66.202.69:7000/api/erccustom/deploy";
			$postData = json_encode($apiArray);
			$response = $this->common_model->curl_url_post($url,$postData);
			
			// $hash = $response->hash;
			// $contractUrl = "52.66.202.69:7000/api/erc/getContractAddress/".$hash;
			// $resp =$this->common_model->curl_url_get($contractUrl);
			
			// echo "<pre>";
			// print_r($response);die();
			if($response->hash)
			{
				$dataArray = array(
									'issuer_id'=>$issuer_id,
									'asset_id' => $aid,
									'token_name' => $name,
									'symbol' => $symbol,
									'hash' => $response->hash
									// 'contract_address' =>$resp->contractAddress
									);
				$tid = $this->common_model->addRecords("token_detail",$dataArray);
				$this->common_model->updateRecords("issuance",array('is_approved'=>1,'approveddate'=>date('Y-m-d H:i:s')),array('asset_id'=>$aid));
				$this->session->set_flashdata('success','Issuance Approved Successfully');
			}
			else
			{
				$this->session->set_flashdata('error','Insuffient fund, Please try again');
			}
			
			redirect('admin/issuance_detail/'.base64_encode($aid));
		}
		else
		{
			$this->session->set_flashdata('error','Please first approve asset then try again');
			redirect('admin/issuance_detail/'.base64_encode($aid));
		}
	}

	public function asset_approve($id)
	{
		$aid = base64_decode($id);	
		$issuer = $this->common_model->getSingleRecordById("asset",array('id'=>$aid));
		$issuer_id = $issuer['issuer_id'];
		$title=" Asset Approved By admin";
	    $message="Your asset was approved by admin";
	    $dataArray = array('notification_type_id'=>2,'recipient_id'=>$issuer_id,'sender_id'=>1,'title'=>$title,'message'=>$message,'link'=>'assetdetail/'.$aid);
	    $this->common_model->addRecords("notification", $dataArray);

		$this->common_model->updateRecords("asset",array('isapproved'=>1,'approveddate'=>date('Y-m-d H:i:s')),array('id'=>$aid));
		$this->session->set_flashdata('success','Assets Approved Successfully');
		redirect('admin/assets_manage');

	}
	public function asset_white_list($id)
	{
		error_reporting(0);
		$aid = base64_decode($id);	
		$contract_detail = $this->common_model->getSingleRecordById("token_detail",array('asset_id'=>$aid));
		$contract_address = $contract_detail['contract_address'];
		
	    $url ="13.233.136.121:7001/api/erc/approveTransfer";
	    $postArray = array(
	    					"from_address"=>"0xFa8e543529D7c46EE03D1953c6219b2583715355",
	    					"from_private_key"=>"0xcc7bedc4c3872d7cf3ebb15ec4c24556d8b2622efa819eba5f7f84c1af602f69",
	    					"contract_address"=>$contract_address
	    				);
	    $post_Array = json_encode($postArray);
	    $responce=$this->common_model->curl_url_post($url,$post_Array);

	   if(empty($responce))
	    {
	    	$this->session->set_flashdata('error','Some Error occur please try again');
			redirect('admin/assets_manage');
	    }
	    else if($responce->hash)
	    {
	    	$this->session->set_flashdata('success','Assets Whitelisted Successfully. Hash - '.$responce->hash);
			redirect('admin/assets_manage');

	    }
	    else if($responce->msg)
	    {
	    	$this->session->set_flashdata('error','Some Error occur please try again');
			redirect('admin/assets_manage');
	    }
	    else
	    {
	    	$this->session->set_flashdata('error','Some Error occur please try again');
			redirect('admin/assets_manage');
	    }
	    
		
	}

	public function asset_black_list($id)
	{
		error_reporting(0);
		$aid = base64_decode($id);	
		$contract_detail = $this->common_model->getSingleRecordById("token_detail",array('asset_id'=>$aid));
		$contract_address = $contract_detail['contract_address'];
		
	    $url ="13.233.136.121:7001/api/erc/disapproveTransfer";
	    $postArray = array(
	    					"from_address"=>"0xFa8e543529D7c46EE03D1953c6219b2583715355",
	    					"from_private_key"=>"0xcc7bedc4c3872d7cf3ebb15ec4c24556d8b2622efa819eba5f7f84c1af602f69",
	    					"contract_address"=>$contract_address
	    				);
	    $post_Array = json_encode($postArray);
	    $responce=$this->common_model->curl_url_post($url,$post_Array);

	   if(empty($responce))
	    {
	    	$this->session->set_flashdata('error','Some Error occur please try again');
			redirect('admin/assets_manage');
	    }
	    else if($responce->hash)
	    {
	    	$this->session->set_flashdata('success','Assets Black Listed Successfully. Hash - '.$responce->hash);
			redirect('admin/assets_manage');

	    }
	    else if($responce->msg)
	    {
	    	$this->session->set_flashdata('error','Some Error occur please try again');
			redirect('admin/assets_manage');
	    }
	    else
	    {
	    	$this->session->set_flashdata('error','Some Error occur please try again');
			redirect('admin/assets_manage');
	    }
	    
		
	}

	public function user_status($id,$typeid)
	{
		$uid = $id;
		$typeid = $typeid;
		
		$userdata = $this->common_model->getSingleRecordById("users",array('id'=>$uid));
		if($userdata['active_status']==0)
		{
			$this->common_model->updateRecords("users",array('active_status'=>1),array('id'=>$uid));
			$this->session->set_flashdata('success','User Activate Successfully');
		}
		else
		{
			$this->common_model->updateRecords("users",array('active_status'=>0),array('id'=>$uid));
			$this->session->set_flashdata('success','User Deactivate Successfully');
		}
		if($typeid==2)
		{
			redirect('admin/issuer_manage');
		}
		else
		{
			redirect('admin/investor_manage');
		}
	}

	public function myprofile()
	{
		// $data['myprofile'] = $this->common_model->getSingleRecordById('users','id='.$this->login_id);
		$adminid = $this->session->userdata('login_id');
		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));
		$q = " SELECT unread_notification_count($adminid) as count";
		$data['notifi_count'] = $this->common_model->getRowByQuery($q);	

		$query="SELECT id as notification_id, getnotificationtype(notification_type_id) as type,link, recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,isread,get_duration(datetime) as duration FROM notification where recipient_id=$adminid and isread=0";
    	$data['notifi_list']= $this->common_model->getArrayByQuery($query);
    	$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
		$this->load->view('admin/admin_profile',$data);
	}

	public function support_manage()
	{
		$adminid = $this->session->userdata('login_id');
		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));
		$query="SELECT s.id,s.ticket_id,s.file,get_admin_unread_support(s.id) as unread,case when u.companyname is null then concat(u.fname,' ',u.lname) else companyname end as name,s.subject,s.description,get_duration(s.datetime) as start_date,case when status=0 then 'Pending' when status=1 then 'Under Progress' when status=2 then 'Completed' end as status from support  as s left join users as u on u.id=s.user_id  order by s.id desc";
      	$data['support_list']= $this->common_model->getArrayByQuery($query);
      	$q = " SELECT unread_notification_count($adminid) as count";
		$data['notifi_count'] = $this->common_model->getRowByQuery($q);	

		$query="SELECT id as notification_id, getnotificationtype(notification_type_id) as type,link, recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,isread,get_duration(datetime) as duration FROM notification where recipient_id=$adminid and isread=0";
    	$data['notifi_list']= $this->common_model->getArrayByQuery($query);
    	$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
		$this->load->view('admin/support_request_list',$data);
	}
	public function support_chat($id)
	{
		$support_id = base64_decode($id);
		$adminid = $this->session->userdata('login_id');
		if(isset($_POST['sendMsg']))
		{
			$msg = $this->input->post('msg');
			$dataArray = array(
		        'support_id'=>$support_id,
		        'reply'=>$msg,
		        'reply_by'=>"admin",
		        'read_by_admin'=>'1'
		    );    
    		$nid=$this->common_model->addRecords('supportdetail', $dataArray);
    		$s = $this->common_model->updateRecords("support",array('status'=>1),array('id'=>$support_id));
		}
		$dataArray = array('read_by_admin'=>'1');
    	$condition = array('support_id' => $support_id);
    	$newid=$this->common_model->updateRecords('supportdetail', $dataArray, $condition);
    	$query="SELECT id,subject, description,ticket_id,file, get_duration(datetime) as posted_date from support where id=$support_id";
    	$data['support']= $this->common_model->getArrayByQuery($query);
		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));
		$query="SELECT id,support_id,reply,reply_by,case when status=0 then 'Pending' when status=1 then 'Under Progress' when status=2 then 'Completed' end as status,get_duration(datetime) as reply_date from supportdetail where support_id=$support_id order by id";
    	$data['supportchat']= $this->common_model->getArrayByQuery($query);
    	$q = " SELECT unread_notification_count($adminid) as count";
		$data['notifi_count'] = $this->common_model->getRowByQuery($q);	

		$query="SELECT id as notification_id, getnotificationtype(notification_type_id) as type,link, recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,isread,get_duration(datetime) as duration FROM notification where recipient_id=$adminid and isread=0";
    	$data['notifi_list']= $this->common_model->getArrayByQuery($query);
    	$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
		$this->load->view('admin/support_chat',$data);
	}
	public function close_support($id)
	{
		$support_id = base64_decode($id);
		$this->common_model->updateRecords("support",array('status'=>2),array('id'=>$support_id));
		$this->session->set_flashdata('success',"Support Closed successfully");
		redirect('admin/support_manage');
	}
 	public function frontend_home()
 	{
 		if (isset($_POST['update_home_content'])) {
                $user_id = $this->session->userdata("login_id");
                $heading = $this->input->post('heading');
                $home_content = $this->input->post('home_content');      
                
                 /*Upload file*/
                if($_FILES['image1']['name']){
                    $imagename  = time().$_FILES['image1']['name'];
                    $tmpname    = $_FILES['image1']['tmp_name'];
                    $dbUrlimg1      = 'uploads/'.  $imagename;
                    move_uploaded_file($tmpname, 'uploads/'.$imagename);                      
                }else{
                    $dbUrlimg1 = $this->input->post("old_image1");
                } 
                if($_FILES['image2']['name']){
                    $imagename  = time().$_FILES['image2']['name'];
                    $tmpname    = $_FILES['image2']['tmp_name'];
                    $dbUrlimg2      = 'uploads/'.  $imagename;
                    move_uploaded_file($tmpname, 'uploads/'.$imagename);                      
                }else{
                    $dbUrlimg2 = $this->input->post("old_image2");
                }    
                /*Upload file end*/

                $post_data = array('heading'=>$heading,'home_data' =>$home_content,'user_id' =>$user_id, 'image1' =>$dbUrlimg1, 'image2' =>$dbUrlimg2,'updated_date' => date("Y-m-d H:i:s")  );

                $getID = $this->common_model->getlastid('tbl_home_content','id');
                $update_dataid = $getID['id'];

                if(empty($update_dataid)){
                    $updatedata = $this->common_model->addRecords('tbl_home_content', $post_data);
                }else{
                    $updatedata = $this->common_model->updateRecords('tbl_home_content', $post_data, array('id' => $update_dataid));
                }             

                if ($updatedata) {
                    $this->session->set_flashdata('success_home_content', 'Home content updated successfully!');
                }else
                {
                    $this->session->set_flashdata('error_home_content', 'Some technical error. Please try again!');
                }
        }
        $data['home_content'] = $this->common_model->getlastid("tbl_home_content",'id');
        $adminid = $this->session->userdata('login_id');
		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));
		$q = " SELECT unread_notification_count($adminid) as count";
		$data['notifi_count'] = $this->common_model->getRowByQuery($q);	

		$query="SELECT id as notification_id, getnotificationtype(notification_type_id) as type,link, recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,isread,get_duration(datetime) as duration FROM notification where recipient_id=$adminid and isread=0";
    	$data['notifi_list']= $this->common_model->getArrayByQuery($query);
    	$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
 		$this->load->view('admin/frontend_home',$data);
 	}

 	public function token_offering()
 	{

 		if (isset($_POST['update_blog_content'])) {
                $user_id = $this->session->userdata("login_id");
                $heading1 = $this->input->post('heading1');
                $blog_content1 = $this->input->post('blog_content1'); 
                $heading2 = $this->input->post('heading2');
                $blog_content2 = $this->input->post('blog_content2');
                $heading3 = $this->input->post('heading3');
                $blog_content3 = $this->input->post('blog_content3');     
                
                 /*Upload file*/
                if($_FILES['image1']['name']){
                    $imagename  = time().$_FILES['image1']['name'];
                    $tmpname    = $_FILES['image1']['tmp_name'];
                    $dbUrlimg1      = 'uploads/'.  $imagename;
                    move_uploaded_file($tmpname, 'uploads/'.$imagename);                      
                }else{
                    $dbUrlimg1 = $this->input->post("old_image1");
                } 
                if($_FILES['image2']['name']){
                    $imagename  = time().$_FILES['image2']['name'];
                    $tmpname    = $_FILES['image2']['tmp_name'];
                    $dbUrlimg2      = 'uploads/'.  $imagename;
                    move_uploaded_file($tmpname, 'uploads/'.$imagename);                      
                }else{
                    $dbUrlimg2 = $this->input->post("old_image2");
                }  
                if($_FILES['image3']['name']){
                    $imagename  = time().$_FILES['image3']['name'];
                    $tmpname    = $_FILES['image3']['tmp_name'];
                    $dbUrlimg3      = 'uploads/'.  $imagename;
                    move_uploaded_file($tmpname, 'uploads/'.$imagename);                      
                }else{
                    $dbUrlimg3 = $this->input->post("old_image3");
                }  
                /*Upload file end*/

                $post_data = array('user_id' =>$user_id,'heading1'=>$heading1,'blog_data1' =>$blog_content1, 'image1' =>$dbUrlimg1,'heading2'=>$heading2,'blog_data2' =>$blog_content2, 'image2' =>$dbUrlimg2,'heading3'=>$heading3,'blog_data3' =>$blog_content3, 'image3' =>$dbUrlimg3,'updated_date' => date("Y-m-d H:i:s")  );

                $getID = $this->common_model->getlastid('tbl_blog_content','id');
                $update_dataid = $getID['id'];

                if(empty($update_dataid)){
                    $updatedata = $this->common_model->addRecords('tbl_blog_content', $post_data);
                }else{
                    $updatedata = $this->common_model->updateRecords('tbl_blog_content', $post_data, array('id' => $update_dataid));
                }             

                if ($updatedata) {
                    $this->session->set_flashdata('success_blog_content', 'Blog content updated successfully!');
                }else
                {
                    $this->session->set_flashdata('error_blog_content', 'Some technical error. Please try again!');
                }
        }
        $data['blog_content'] = $this->common_model->getlastid("tbl_blog_content",'id');
        $adminid = $this->session->userdata('login_id');
		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));
		$q = " SELECT unread_notification_count($adminid) as count";
		$data['notifi_count'] = $this->common_model->getRowByQuery($q);	

		$query="SELECT id as notification_id, getnotificationtype(notification_type_id) as type,link, recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,isread,get_duration(datetime) as duration FROM notification where recipient_id=$adminid and isread=0";
    	$data['notifi_list']= $this->common_model->getArrayByQuery($query);
    	$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
 		$this->load->view('admin/frontend_blog',$data);
 	}

 	public function frontend_stories()
 	{
 		if (isset($_POST['update_story_content'])) {
                $user_id = $this->session->userdata("login_id");
                $heading1 = $this->input->post('heading1');
                $story_content1 = $this->input->post('story_content1'); 
                $raised1 = $this->input->post('raised1');
                $investor1 = $this->input->post('investor1');
                $valueable1 = $this->input->post('valueable1');
                $heading2 = $this->input->post('heading2');
                $story_content2 = $this->input->post('story_content2');  
                $raised2 = $this->input->post('raised2');
                $investor2 = $this->input->post('investor2');
                $valueable2 = $this->input->post('valueable2');              
                
                 /*Upload file*/
                if($_FILES['image1']['name']){
                    $imagename  = time().$_FILES['image1']['name'];
                    $tmpname    = $_FILES['image1']['tmp_name'];
                    $dbUrlimg1      = 'uploads/'.  $imagename;
                    move_uploaded_file($tmpname, 'uploads/'.$imagename);                      
                }else{
                    $dbUrlimg1 = $this->input->post("old_image1");
                } 
                if($_FILES['image2']['name']){
                    $imagename  = time().$_FILES['image2']['name'];
                    $tmpname    = $_FILES['image2']['tmp_name'];
                    $dbUrlimg2      = 'uploads/'.  $imagename;
                    move_uploaded_file($tmpname, 'uploads/'.$imagename);                      
                }else{
                    $dbUrlimg2 = $this->input->post("old_image2");
                }             
                /*Upload file end*/

                $post_data = array('user_id' =>$user_id,'heading1'=>$heading1,'story_data1' =>$story_content1, 'image1' =>$dbUrlimg1,'raised1'=>$raised1,'investors1'=>$investor1,'valueable1'=>$valueable1,'heading2'=>$heading2,'story_data2' =>$story_content2, 'image2' =>$dbUrlimg2,'raised2'=>$raised2,'investors2'=>$investor2,'valueable2'=>$valueable2,'updated_date' => date("Y-m-d H:i:s")  );

                $getID = $this->common_model->getlastid('tbl_story_content','id');
                $update_dataid = $getID['id'];

                if(empty($update_dataid)){
                    $updatedata = $this->common_model->addRecords('tbl_story_content', $post_data);
                }else{
                    $updatedata = $this->common_model->updateRecords('tbl_story_content', $post_data, array('id' => $update_dataid));
                }             

                if ($updatedata) {
                    $this->session->set_flashdata('success_story_content', 'Story content updated successfully!');
                }else
                {
                    $this->session->set_flashdata('error_story_content', 'Some technical error. Please try again!');
                }
        }
        $data['story_content'] = $this->common_model->getlastid("tbl_story_content",'id');
        $adminid = $this->session->userdata('login_id');
		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));
		$q = " SELECT unread_notification_count($adminid) as count";
		$data['notifi_count'] = $this->common_model->getRowByQuery($q);	

		$query="SELECT id as notification_id, getnotificationtype(notification_type_id) as type,link, recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,isread,get_duration(datetime) as duration FROM notification where recipient_id=$adminid and isread=0";
    	$data['notifi_list']= $this->common_model->getArrayByQuery($query);
    	$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
 		$this->load->view('admin/frontend_stories',$data);
 	}

 	public function frontend_news()
 	{
 		if (isset($_POST['update_new_content'])) {
                $user_id = $this->session->userdata("login_id");
                $heading1 = $this->input->post('heading1');
                $news_content1 = $this->input->post('news_content1'); 
                $heading2 = $this->input->post('heading2');
                $news_content2 = $this->input->post('news_content2');
                $heading3 = $this->input->post('heading3');
                $news_content3 = $this->input->post('news_content3');
                $heading4 = $this->input->post('heading4');
                $news_content4 = $this->input->post('news_content4'); 
                $heading5 = $this->input->post('heading5');
                $news_content5 = $this->input->post('news_content5'); 
                $heading6 = $this->input->post('heading6');
                $news_content6 = $this->input->post('news_content6'); 
                $heading7 = $this->input->post('heading7');
                $news_content7 = $this->input->post('news_content7');  
                
                 /*Upload file*/
                if($_FILES['image1']['name']){
                    $imagename  = time().$_FILES['image1']['name'];
                    $tmpname    = $_FILES['image1']['tmp_name'];
                    $dbUrlimg1      = 'uploads/'.  $imagename;
                    move_uploaded_file($tmpname, 'uploads/'.$imagename);                      
                }else{
                    $dbUrlimg1 = $this->input->post("old_image1");
                } 
                if($_FILES['image2']['name']){
                    $imagename  = time().$_FILES['image2']['name'];
                    $tmpname    = $_FILES['image2']['tmp_name'];
                    $dbUrlimg2      = 'uploads/'.  $imagename;
                    move_uploaded_file($tmpname, 'uploads/'.$imagename);                      
                }else{
                    $dbUrlimg2 = $this->input->post("old_image2");
                }  
                if($_FILES['image3']['name']){
                    $imagename  = time().$_FILES['image3']['name'];
                    $tmpname    = $_FILES['image3']['tmp_name'];
                    $dbUrlimg3      = 'uploads/'.  $imagename;
                    move_uploaded_file($tmpname, 'uploads/'.$imagename);                      
                }else{
                    $dbUrlimg3 = $this->input->post("old_image3");
                }
                if($_FILES['image4']['name']){
                    $imagename  = time().$_FILES['image4']['name'];
                    $tmpname    = $_FILES['image4']['tmp_name'];
                    $dbUrlimg4      = 'uploads/'.  $imagename;
                    move_uploaded_file($tmpname, 'uploads/'.$imagename);                      
                }else{
                    $dbUrlimg4 = $this->input->post("old_image4");
                } 
                if($_FILES['image5']['name']){
                    $imagename  = time().$_FILES['image5']['name'];
                    $tmpname    = $_FILES['image5']['tmp_name'];
                    $dbUrlimg5      = 'uploads/'.  $imagename;
                    move_uploaded_file($tmpname, 'uploads/'.$imagename);                      
                }else{
                    $dbUrlimg5 = $this->input->post("old_image5");
                } 
                if($_FILES['image6']['name']){
                    $imagename  = time().$_FILES['image6']['name'];
                    $tmpname    = $_FILES['image6']['tmp_name'];
                    $dbUrlimg6      = 'uploads/'.  $imagename;
                    move_uploaded_file($tmpname, 'uploads/'.$imagename);                      
                }else{
                    $dbUrlimg6 = $this->input->post("old_image6");
                } 
                if($_FILES['image7']['name']){
                    $imagename  = time().$_FILES['image7']['name'];
                    $tmpname    = $_FILES['image7']['tmp_name'];
                    $dbUrlimg7      = 'uploads/'.  $imagename;
                    move_uploaded_file($tmpname, 'uploads/'.$imagename);                      
                }else{
                    $dbUrlimg7 = $this->input->post("old_image7");
                }   
                /*Upload file end*/

                $post_data = array('user_id' =>$user_id,'heading1'=>$heading1,'news_data1' =>$news_content1, 'image1' =>$dbUrlimg1,'heading2'=>$heading2,'news_data2' =>$news_content2, 'image2' =>$dbUrlimg2,'heading3'=>$heading3,'news_data3' =>$news_content3, 'image3' =>$dbUrlimg3,'heading4'=>$heading4,'news_data4' =>$news_content4, 'image4' =>$dbUrlimg4,'heading5'=>$heading5,'news_data5' =>$news_content5, 'image5' =>$dbUrlimg5,'heading6'=>$heading6,'news_data6' =>$news_content6, 'image6' =>$dbUrlimg6,'heading7'=>$heading7,'news_data7' =>$news_content7, 'image7' =>$dbUrlimg7,'updated_date' => date("Y-m-d H:i:s")  );
                

                $getID = $this->common_model->getlastid('tbl_news_content','id');
                $update_dataid = $getID['id'];

                if(empty($update_dataid)){
                    $updatedata = $this->common_model->addRecords('tbl_news_content', $post_data);
                }else{
                    $updatedata = $this->common_model->updateRecords('tbl_news_content', $post_data, array('id' => $update_dataid));
                }             

                if ($updatedata) {
                    $this->session->set_flashdata('success_news_content', 'News content updated successfully!');
                }else
                {
                    $this->session->set_flashdata('error_news_content', 'Some technical error. Please try again!');
                }
        }
        $data['news_content'] = $this->common_model->getlastid("tbl_news_content",'id');
        $adminid = $this->session->userdata('login_id');
		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));
		$q = " SELECT unread_notification_count($adminid) as count";
		$data['notifi_count'] = $this->common_model->getRowByQuery($q);	

		$query="SELECT id as notification_id, getnotificationtype(notification_type_id) as type,link, recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,isread,get_duration(datetime) as duration FROM notification where recipient_id=$adminid and isread=0";
    	$data['notifi_list']= $this->common_model->getArrayByQuery($query);
    	$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
 		$this->load->view('admin/frontend_news',$data);
 	}

 	public function frontend_contact()
 	{
 		if (isset($_POST['update_contact_content'])) {
                $user_id = $this->session->userdata("login_id");
                $address = $this->input->post('address');
                $contact_number = $this->input->post('contact_number'); 
                $email = $this->input->post('email');                             
              

                $post_data = array('user_id' =>$user_id,'address'=>$address,'contact_number' =>$contact_number, 'email' =>$email,'date_time' => date("Y-m-d H:i:s")  );

                $getID = $this->common_model->getlastid('tbl_contact_us','id');
                $update_dataid = $getID['id'];

                if(empty($update_dataid)){
                    $updatedata = $this->common_model->addRecords('tbl_contact_us', $post_data);
                }else{
                    $updatedata = $this->common_model->updateRecords('tbl_contact_us', $post_data, array('id' => $update_dataid));
                }             

                if ($updatedata) {
                    $this->session->set_flashdata('success_contact_content', 'Contact Data updated successfully!');
                }else
                {
                    $this->session->set_flashdata('error_contact_content', 'Some technical error. Please try again!');
                }
        }
        $data['contact_content'] = $this->common_model->getlastid("tbl_contact_us",'id');
        $adminid = $this->session->userdata('login_id');
		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));
		$q = " SELECT unread_notification_count($adminid) as count";
		$data['notifi_count'] = $this->common_model->getRowByQuery($q);	

		$query="SELECT id as notification_id, getnotificationtype(notification_type_id) as type,link, recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,isread,get_duration(datetime) as duration FROM notification where recipient_id=$adminid and isread=0";
    	$data['notifi_list']= $this->common_model->getArrayByQuery($query);
    	$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
 		$this->load->view('admin/frontend_contactus',$data);
 	}

 	public function privacy_policy()
 	{
 		if(isset($_POST['privacy_policy']))
 		{
 			$privacy_content =$this->input->post('privacy_content');
 			

 			$getID = $this->common_model->getlastid('privacy_policy','id');
                $update_dataid = $getID['id'];

                if(empty($update_dataid)){
                    $updatedata = $this->common_model->addRecords('privacy_policy', array('content'=>$privacy_content));
                }else{
                    $updatedata = $this->common_model->updateRecords('privacy_policy', array('content'=>$privacy_content), array('id' => $update_dataid));
                }             

                if ($updatedata) {
                    $this->session->set_flashdata('success', 'Privacy Data update successfully!');
                }else
                {
                    $this->session->set_flashdata('error', 'Some technical error. Please try again!');
                }
 		}
 		$adminid = $this->session->userdata('login_id');
		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));
		$q = " SELECT unread_notification_count($adminid) as count";
		$data['notifi_count'] = $this->common_model->getRowByQuery($q);	

		$query="SELECT id as notification_id, getnotificationtype(notification_type_id) as type,link, recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,isread,get_duration(datetime) as duration FROM notification where recipient_id=$adminid and isread=0";
    	$data['notifi_list']= $this->common_model->getArrayByQuery($query);
    	$data['privacy_data']=$this->common_model->getSingleRecordById('privacy_policy',array());
    	$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
 		$this->load->view('admin/privacy_policy',$data);
 	}

 	public function terms_condition()
 	{
 		if(isset($_POST['terms_condition']))
 		{
 			$terms_content =$this->input->post('terms_content');
 			

 			$getID = $this->common_model->getlastid('term_condition','id');
                $update_dataid = $getID['id'];

                if(empty($update_dataid)){
                    $updatedata = $this->common_model->addRecords('term_condition', array('content'=>$terms_content));
                }else{
                    $updatedata = $this->common_model->updateRecords('term_condition', array('content'=>$terms_content), array('id' => $update_dataid));
                }             

                if ($updatedata) {
                    $this->session->set_flashdata('success', 'Terms Condition Data update successfully!');
                }else
                {
                    $this->session->set_flashdata('error', 'Some technical error. Please try again!');
                }
 		}
 		$adminid = $this->session->userdata('login_id');
		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));
		$q = " SELECT unread_notification_count($adminid) as count";
		$data['notifi_count'] = $this->common_model->getRowByQuery($q);	

		$query="SELECT id as notification_id, getnotificationtype(notification_type_id) as type,link, recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,isread,get_duration(datetime) as duration FROM notification where recipient_id=$adminid and isread=0";
    	$data['notifi_list']= $this->common_model->getArrayByQuery($query);
    	$data['terms_data']=$this->common_model->getSingleRecordById('term_condition',array());
    	$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
 		$this->load->view('admin/term_condition',$data);
 	}

 	public function blog()
 	{
 		$adminid = $this->session->userdata('login_id');
		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));
		$q = " SELECT unread_notification_count($adminid) as count";
		$data['notifi_count'] = $this->common_model->getRowByQuery($q);	

		$query="SELECT id as notification_id, getnotificationtype(notification_type_id) as type,link, recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,isread,get_duration(datetime) as duration FROM notification where recipient_id=$adminid and isread=0";
    	$data['notifi_list']= $this->common_model->getArrayByQuery($query);
    	$data['blog_data']= $this->common_model->getAllRecordsById("blog",array());
    	$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
 		$this->load->view('admin/blog_list',$data);
 	}

 	public function add_blog()
 	{
 		if(isset($_POST['add_blog']))
 		{
 			$data = $this->input->post();

 			if($_FILES['image']['name']){
                    $imagename  = time().$_FILES['image']['name'];
                    $tmpname    = $_FILES['image']['tmp_name'];
                    $image      = 'uploads/'.  $imagename;
                    move_uploaded_file($tmpname, 'uploads/'.$imagename);                      
                }else{
                    $image = $this->input->post("old_image");
                } 
 			$dataArray = array(
 							'title'=>$data['title'],
 							'description' =>$data['content'],
 							'image'	=>$image
 							);
 			$this->common_model->addRecords("blog",$dataArray);
 			$this->session->set_flashdata('success','Blog added successfully');
 		}
 		$adminid = $this->session->userdata('login_id');
		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));
		$q = " SELECT unread_notification_count($adminid) as count";
		$data['notifi_count'] = $this->common_model->getRowByQuery($q);	

		$query="SELECT id as notification_id, getnotificationtype(notification_type_id) as type,link, recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,isread,get_duration(datetime) as duration FROM notification where recipient_id=$adminid and isread=0";
    	$data['notifi_list']= $this->common_model->getArrayByQuery($query);
    	$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
    	$this->load->view('admin/add_blog',$data);
 	}

 	public function edit_blog($id)
 	{
 		$id=base64_decode($id);
 		if(isset($_POST['edit_blog']))
 		{
 			$data = $this->input->post();

 			if($_FILES['image']['name']){
                    $imagename  = time().$_FILES['image']['name'];
                    $tmpname    = $_FILES['image']['tmp_name'];
                    $image      = 'uploads/'.  $imagename;
                    move_uploaded_file($tmpname, 'uploads/'.$imagename);                      
                }else{
                    $image = $this->input->post("old_image");
                } 
 			$dataArray = array(
 							'title'=>$data['title'],
 							'description' =>$data['content'],
 							'image'	=>$image
 							);
 			$this->common_model->updateRecords("blog",$dataArray,array('id'=>$id));
 			$this->session->set_flashdata('success','Blog Update successfully');
 		}
 		$adminid = $this->session->userdata('login_id');
		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));
		$q = " SELECT unread_notification_count($adminid) as count";
		$data['notifi_count'] = $this->common_model->getRowByQuery($q);	

		$query="SELECT id as notification_id, getnotificationtype(notification_type_id) as type,link, recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,isread,get_duration(datetime) as duration FROM notification where recipient_id=$adminid and isread=0";
    	$data['notifi_list']= $this->common_model->getArrayByQuery($query);
    	$data['blogs'] = $this->common_model->getSingleRecordById("blog",array('id'=>$id));
    	$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
    	$this->load->view('admin/edit_blog',$data);
 	}
 	public function delete_blog($id)
 	{
 		$id = base64_decode($id);
 		$this->common_model->deleteRecords("blog",array('id'=>$id));
 		$this->session->set_flashdata('success','Blog deleted successfully');
 		redirect("admin/blog");
 	}
	public function update_profile()
	{
		$uid = $this->session->userdata('login_id');
		if(isset($_POST['update_profile']))
		{
			$username = $this->input->post('username');
			$email = $this->input->post('email');
			$mobile = $this->input->post('mobile');
			$address = $this->input->post('address');
			
			if($_FILES['image']['name'])
			{
	            $imagename  = time().$_FILES['image']['name'];
	            $tmpname    = $_FILES['image']['tmp_name'];
	            $dbUrlimg      = 'uploads/user_profile/'.  $imagename;
	            move_uploaded_file($tmpname, 'uploads/user_profile/'.$imagename);                      
	        }
	        else
	        {
		        $dbUrlimg = $this->input->post("old_image");
	        }
			$post_data = array(
				'fullname'=>$username,
				'email'=>$email,
				'mobile'=>$mobile,
				'city'=>$address,			
				'profile_pic'=>$dbUrlimg
			);			
			$check =  $this->common_model->updateData('users',$post_data,'id='.$uid);
			if($check){
				$this->session->set_flashdata('success','Your Profile Successfully Updated.');
			}else{
				$this->session->set_flashdata('error',' Error, Please try again.');
			}
		}
		$data['profile_data'] = $this->common_model->getSingleRecordById('users','id='.$uid);
		$adminid = $this->session->userdata('login_id');
		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));
		$q = " SELECT unread_notification_count($adminid) as count";
		$data['notifi_count'] = $this->common_model->getRowByQuery($q);	

		$query="SELECT id as notification_id, getnotificationtype(notification_type_id) as type,link, recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,isread,get_duration(datetime) as duration FROM notification where recipient_id=$adminid and isread=0";
    	$data['notifi_list']= $this->common_model->getArrayByQuery($query);
    	$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
		// print_r($data);die();
		$this->load->view('admin/update_profile',$data);
	}

	public function mypassword()
	{
		$uid =$this->session->userdata('login_id');
		if(isset($_POST['change_password']))
		{
			$data = $this->input->post();
			$old_password = $data['old_password'];
			$new_password = $data['new_password'];
			$con_password = $data['confirm_password'];
			$check = $this->common_model->getSingleRecordById('users','password="'.md5($old_password).'" AND id='.$uid);
			if(empty($check))
			{
				$this->session->set_flashdata('error','Your Current Password Is Incorrect !');
				redirect('admin/mypassword');
			}
			if($new_password != $con_password)
			{
				$this->session->set_flashdata('error','Password and confirmation password does not match.');
				redirect('admin/mypassword');
			}
			$post_data = array(
				'password'=>md5($new_password),
			);
			$check =  $this->common_model->updateData('users',$post_data,'id='.$uid);
			if($check){
				$this->session->set_flashdata('success','Your Password Successfully Updated.');
			}else{
				$this->session->set_flashdata('error','Server Error, Please try again.');
			}
		}
		$adminid = $this->session->userdata('login_id');
		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));
		$q = " SELECT unread_notification_count($adminid) as count";
		$data['notifi_count'] = $this->common_model->getRowByQuery($q);	

		$query="SELECT id as notification_id, getnotificationtype(notification_type_id) as type,link, recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,isread,get_duration(datetime) as duration FROM notification where recipient_id=$adminid and isread=0";
    	$data['notifi_list']= $this->common_model->getArrayByQuery($query);
    	$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
		$this->load->view('admin/mypassword',$data);
	}
	public function datatable()
	{
		$this->load->view('admin/datatable');
	}
	
	public function forgot_password()
	{
		$adminid = $this->session->userdata('login_id');
		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));
		$q = " SELECT unread_notification_count($adminid) as count";
		$data['notifi_count'] = $this->common_model->getRowByQuery($q);	

		$query="SELECT id as notification_id, getnotificationtype(notification_type_id) as type,link, recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,isread,get_duration(datetime) as duration FROM notification where recipient_id=$adminid and isread=0";
    	$data['notifi_list']= $this->common_model->getArrayByQuery($query);
    	$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
		$this->load->view('admin/forgot_password',$data);
	}

	public function approval_request()
	{
		$query = "SELECT officerdetail.*,users.companyname from officerdetail left join users on officerdetail.userid=users.id where officerdetail.isapproved = 0 ";
		$data['request_data'] = $this->common_model->getArrayByQuery($query);
		$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
		$this->load->view('admin/officer_request_list',$data);
	}

	public function approved_list()
	{
		$query = "SELECT officerdetail.*,users.companyname from officerdetail left join users on officerdetail.userid=users.id where officerdetail.isapproved = 1 ";
		$data['approve_data'] = $this->common_model->getArrayByQuery($query);
		$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());	
		$this->load->view('admin/officer_approve_list',$data);
	}

	public function officer_status($id)
	{
		$aid = $id;
		$this->common_model->updateRecords("officerdetail",array('isapproved'=>1),array('id'=>$aid));
		$this->session->set_flashdata('success','Officer Approved Successfully');
		redirect('admin/approval_request');
	}
	public function notification_list()
	{
		$adminid = $this->session->userdata('login_id');
		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));
		$q = " SELECT unread_notification_count($adminid) as count";
		$data['notifi_count'] = $this->common_model->getRowByQuery($q);	

		$query="SELECT id as notification_id, getnotificationtype(notification_type_id) as type,link, recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,isread,get_duration(datetime) as duration FROM notification where recipient_id=$adminid and isread=0";
    	$data['notifi_list']= $this->common_model->getArrayByQuery($query);		

    	$qury="SELECT id as notification_id, getnotificationtype(notification_type_id) as type,link, recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,isread,get_duration(datetime) as duration FROM notification where recipient_id=$adminid";
    	$data['notification_list']= $this->common_model->getArrayByQuery($qury);
    	$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());	
		$this->load->view('admin/notification_list',$data);
	}

	public function notification_manage()
	{
		// $data['not_data'] = $this->common_model->getAllRecords("subscribers");
		$adminid = $this->session->userdata('login_id');
		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));
		$q = " SELECT unread_notification_count($adminid) as count";
		$data['notifi_count'] = $this->common_model->getRowByQuery($q);	

		$query="SELECT id as notification_id, getnotificationtype(notification_type_id) as type,link, recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,isread,get_duration(datetime) as duration FROM notification where recipient_id=$adminid and isread=0";
    	$data['notifi_list']= $this->common_model->getArrayByQuery($query);	
    	$qu = "SELECT user_notification.*, concat(users.fname,' ',users.lname) as receiver from user_notification left join users on user_notification.recipient_id=users.id";
    	$data['notification_data'] = $this->common_model->getArrayByQuery($qu);	
    	$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
		$this->load->view('admin/notification_manage',$data);
	}
	public function send_notification()
	{
		// error_reporting(0);
		$adminid = $this->session->userdata('login_id');
		if(isset($_POST['sendNotification']))
		{
			$data =$this->input->post();
			$recipients = $data['recipients'];
			// echo "<pre>";
			// print_r($recipients);die();

			for($i=0;$i<sizeof($recipients);$i++)
			{
				
				$recip = $recipients[$i];				
				$subject = $data['subject'];
				$msg = $data['msg'];
				$a = $this->email_confirmation($recip,$subject,$msg);

				$recipData = $this->common_model->getSingleRecordById("users",array('email'=>$recip));
				$recip_id =$recipData['id'];

				$dataArray = array(
								'sender_id'=>$adminid,
								'recipient_id' =>$recip_id,
								'subject' =>$subject,
								'notification' => $msg
								);
			
				$this->common_model->addRecords("user_notification",$dataArray);
			}
			
			
			$this->session->set_flashdata('success','Notification send Successfully');			
		}
		
		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));
		$q = " SELECT unread_notification_count($adminid) as count";
		$data['notifi_count'] = $this->common_model->getRowByQuery($q);	

		$query="SELECT id as notification_id, getnotificationtype(notification_type_id) as type,link, recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,isread,get_duration(datetime) as duration FROM notification where recipient_id=$adminid and isread=0";
    	$data['notifi_list']= $this->common_model->getArrayByQuery($query);
    	$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
		$this->load->view('admin/send_notification',$data);
		
	}
	public function getuserForNotification()
	{
		$id= $_POST['user'];
		$query = "SELECT id,concat(fname,' ',lname) as username,email from  users where usertypeid=$id and active_status=1";
		$userData = $this->common_model->getArrayByQuery($query);
		echo json_encode($userData);
	}

	public function multicheck()
	{
		// $query = "SELECT id,concat(fname,' ',lname) as username from  users where usertypeid=2 and active_status=1";
		// $userData = $this->common_model->getArrayByQuery($query);
		// echo "<pre>";
		// print_r($userData);die();
		$this->load->view('admin/multicheck');
	}
	public function admin_settings()
	{
		if(isset($_POST['add_commission']))
		{
			$commission_fees = $this->input->post('commission_fees');
			$this->common_model->updateRecords("admin_settings",array('commission_fees'=>$commission_fees),array());
			$this->session->set_flashdata('success','Commission Fees update Successfully');
			redirect('admin/admin_settings');
		}
		$data['commission'] = $this->common_model->getSingleRecordById("admin_settings",array());
		$adminid = $this->session->userdata('login_id');
		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));
		$q = " SELECT unread_notification_count($adminid) as count";
		$data['notifi_count'] = $this->common_model->getRowByQuery($q);	

		$query="SELECT id as notification_id, getnotificationtype(notification_type_id) as type,link, recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,isread,get_duration(datetime) as duration FROM notification where recipient_id=$adminid and isread=0";
    	$data['notifi_list']= $this->common_model->getArrayByQuery($query);
    	$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
		$this->load->view('admin/settings',$data);
	}

	public function view_issuer($id)
	{
		$data['officer_data'] = $this->common_model->getAllRecordsById("officerdetail",array('userid'=>$id));
		$data['issuer_info'] = $this->common_model->getSingleRecordById("users",array('id'=>$id));
		$adminid = $this->session->userdata('login_id');
		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));
		$q = " SELECT unread_notification_count($adminid) as count";
		$data['notifi_count'] = $this->common_model->getRowByQuery($q);	

		$query="SELECT id as notification_id, getnotificationtype(notification_type_id) as type,link, recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,isread,get_duration(datetime) as duration FROM notification where recipient_id=$adminid and isread=0";
    	$data['notifi_list']= $this->common_model->getArrayByQuery($query);
    	$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
		$this->load->view('admin/issuer_profile',$data);
	}

	public function view_officer($id)
	{
		$adminid = $this->session->userdata('login_id');
		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));
		$data['officer_info'] = $this->common_model->getSingleRecordById("officerdetail",array('id'=>$id));
		$q = " SELECT unread_notification_count($adminid) as count";
		$data['notifi_count'] = $this->common_model->getRowByQuery($q);	

		$query="SELECT id as notification_id, getnotificationtype(notification_type_id) as type,link, recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,isread,get_duration(datetime) as duration FROM notification where recipient_id=$adminid and isread=0";
    	$data['notifi_list']= $this->common_model->getArrayByQuery($query);
    	$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
		$this->load->view('admin/officer_profile',$data);
	}

	public function issuer_edit($id)
	{
		if(isset($_POST['updateBtn']))
		{
			$data = $this->input->post();
			$postData = array(
							'companyname' => $data['name'],
							'mobile' => $data['phone_number'],
							'gender' => $data['gender']	
						);
			$d = $this->common_model->updateRecords("users",$postData,array('id'=>$id));
			$this->session->set_flashdata('success','Issuer info update Successfully');
			redirect('admin/issuer_manage');
		}
		
		$data['issuer_info'] = $this->common_model->getSingleRecordById("users",array('id'=>$id));
		$adminid = $this->session->userdata('login_id');
		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));
		$q = " SELECT unread_notification_count($adminid) as count";
		$data['notifi_count'] = $this->common_model->getRowByQuery($q);	

		$query="SELECT id as notification_id, getnotificationtype(notification_type_id) as type,link, recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,isread,get_duration(datetime) as duration FROM notification where recipient_id=$adminid and isread=0";
    	$data['notifi_list']= $this->common_model->getArrayByQuery($query);
    	$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
		$this->load->view('admin/issuer_edit',$data);
	}

	public function issuer_delete($id)
	{
		$this->common_model->deleteRecords("officerdetail",array('userid'=>$id));
		$this->common_model->deleteRecords("investor_kyc",array('user_id'=>$id));
		$this->common_model->deleteRecords("users",array('id'=>$id));		
		$this->session->set_flashdata('success','Issuer info  Deleted Successfully');
		redirect('admin/issuer_manage');
	}

	public function view_investor($id)
	{
		$adminid = $this->session->userdata('login_id');
		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));
		$data['investor_info'] = $this->common_model->getSingleRecordById("users",array('id'=>$id));
		$q = " SELECT unread_notification_count($adminid) as count";
		$data['notifi_count'] = $this->common_model->getRowByQuery($q);	

		$query="SELECT id as notification_id, getnotificationtype(notification_type_id) as type,link, recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,isread,get_duration(datetime) as duration FROM notification where recipient_id=$adminid and isread=0";
    	$data['notifi_list']= $this->common_model->getArrayByQuery($query);
    	$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
		$this->load->view('admin/investor_profile',$data);
	}

	public function investor_delete($id)
	{
		
		$this->common_model->deleteRecords("users",array('id'=>$id));		
		$this->session->set_flashdata('success','Investor info  Deleted Successfully');
		redirect('admin/investor_manage');
	}

	public function investor_edit($id)
	{
		if(isset($_POST['updateBtn']))
		{
			$data = $this->input->post();
			$postData = array(
							'fname' => $data['fname'],
							'lname' => $data['lname'],
							'mobile' => $data['phone_number'],
							'gender' => $data['gender']	
						);
			$d = $this->common_model->updateRecords("users",$postData,array('id'=>$id));
			$this->session->set_flashdata('success','Investor info update Successfully');
			redirect('admin/investor_manage');
		}
		
		$data['investor_info'] = $this->common_model->getSingleRecordById("users",array('id'=>$id));
		$adminid = $this->session->userdata('login_id');
		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));
		$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
		$this->load->view('admin/investor_edit',$data);
	}

	public function view_assets($id)
	{
		$adminid = $this->session->userdata('login_id');
		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));
		$query = "SELECT asset.*,users.companyname from asset left join users on asset.issuer_id=users.id where asset.id = $id";
		$data['assets_info'] = $this->common_model->getRowByQuery($query);		
		$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
		$this->load->view('admin/assets_view',$data);
	}

	public function transaction_manage()
	{
		$adminid = $this->session->userdata('login_id');

		$query="SELECT i.id,getassetissuer(i.asset_id) as issuer_name,concat(u.fname,' ',u.lname) as investor_name,a.name as asset_name,wallet_address(i.user_id) as wallet_address,get_user_email(i.user_id) as email, date_format(i.datetime,'%d %M %Y') as invest_date,date_format(i.datetime,'%H:%i:%s') as invest_time,ic.startdate, ic.enddate, coalesce(i.investment_amount,'') as investment_amount, coalesce(ceil(i.investment_amount/price_per_unit),'') as units from investment as i left join asset as a on a.id=i.asset_id left join issuance as ic on ic.asset_id=i.asset_id left join users as u on u.id=i.user_id order by i.id desc  ";
         $data['transaction_data'] = $this->common_model->getArrayByQuery($query);

		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));
		$q = " SELECT unread_notification_count($adminid) as count";
		$data['notifi_count'] = $this->common_model->getRowByQuery($q);	

		$query="SELECT id as notification_id, getnotificationtype(notification_type_id) as type,link, recipient_id,getcompanyname(recipient_id) as recipient,sender_id,getcompanyname(sender_id) as sender,title,message,isread,get_duration(datetime) as duration FROM notification where recipient_id=$adminid and isread=0";
    	$data['notifi_list']= $this->common_model->getArrayByQuery($query);
    	$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
		$this->load->view('admin/transaction_manage',$data);
	}

	public function user_list()
	{
		// error_reporting(0);
		// $type = $this->uri->segment(3);
		// $data['user_list'] =  $this->common_model->getAllRecordsOrderById('users','fullname','ASC', array('active_status' =>  $type,'admin_id'=>$this->login_id));

		// $table='tags_list'; $conditions ='admin_id = '.$this->login_id.' AND  tags_name !="" ORDER  BY datetime DESC';
		// $data['tags_list'] = $this->common_model->getAllRecordsById($table,$conditions);
		// $data['user_tags'] = $this->common_model->fetch_user_tag($this->login_id,'');
		$adminid = $this->session->userdata('login_id');
		$data['admindata'] = $this->common_model->getSingleRecordById("users",array('id'=>$adminid));
		$data['logo_detail'] = $this->common_model->getSingleRecordById("tbl_title_logo",array());
		$this->load->view('admin/user_list',$data);
	}

	public function logout()
	{
		$this->session->unset_userdata('login_id');	
		$this->session->unset_userdata('admin_type');
		$this->session->unset_userdata('admin_name');

		redirect('admin/index');
	}
	

	
}

