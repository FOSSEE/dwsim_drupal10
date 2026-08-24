<?php

/*function proposal_edit_form_validate($form, &$form_state)
{
		/* mobile */
		/*if (!preg_match('/^[0-9\ \+]{0,15}$/', $form_state['values']['mobile']))
			form_set_error('mobile', t('Invalid mobile number'));

		/* date of completion */
		/*if (!preg_match('/^[0-9]{1,2}-[0-9]{1,2}-[0-9]{4}$/', $form_state['values']['completion_date']))
			form_set_error('completion_date', t('Invalid expected date of completion'));

		list($d, $m, $y) = explode('-', $form_state['values']['completion_date']);
		$d = (int)$d; $m = (int)$m; $y = (int)$y;
		
		if (!checkdate($m, $d, $y))
			form_set_error('completion_date', t('Invalid expected date of completion'));
		
		if (mktime(0, 0, 0, $m, $d, $y) <= time())
			form_set_error('completion_date', t('Expected date of completion should be in future'));  

		/* edition */
		/*if (!preg_match('/^[1-9][0-9]{0,1}$/', $form_state['values']['edition1']))
			form_set_error('edition1', t('Invalid edition for Book Preference 1'));

		if (!preg_match('/^[1-9][0-9]{0,1}$/', $form_state['values']['edition2']))
			form_set_error('edition2', t('Invalid edition for Book Preference 2'));
		if (!preg_match('/^[1-9][0-9]{0,1}$/', $form_state['values']['edition3']))
			form_set_error('edition3', t('Invalid edition for Book Preference 3'));

		/* year of publication */
		/*if (!preg_match('/^[1-3][0-9][0-9][0-9]$/', $form_state['values']['year1']))
			form_set_error('year1', t('Invalid year of pulication for Book Preference 1'));

		if (!preg_match('/^[1-3][0-9][0-9][0-9]$/', $form_state['values']['year2']))
			form_set_error('year2', t('Invalid year of pulication for Book Preference 2'));

		if (!preg_match('/^[1-3][0-9][0-9][0-9]$/', $form_state['values']['year3']))
			form_set_error('year3', t('Invalid year of pulication for Book Preference 3'));

		/* year of publication */
		/*$cur_year = date('Y');
		if ((int)$form_state['values']['year1'] > $cur_year)
			form_set_error('year1', t('Year of pulication should be not in the future for Book Preference 1'));
		
		if ((int)$form_state['values']['year2'] > $cur_year)
			form_set_error('year2', t('Year of pulication should be not in the future for Book Preference 2'));
		
		if ((int)$form_state['values']['year3'] > $cur_year)
			form_set_error('year3', t('Year of pulication should be not in the future for Book Preference 3'));

		/* isbn */
		/*if (!preg_match('/^[0-9\-xX]+$/', $form_state['values']['isbn1']))
			form_set_error('isbn1', t('Invalid ISBN for Book Preference 1'));
		
		if (!preg_match('/^[0-9\-xX]+$/', $form_state['values']['isbn2']))
			form_set_error('isbn2', t('Invalid ISBN for Book Preference 2'));
		
		if (!preg_match('/^[0-9\-xX]+$/', $form_state['values']['isbn3']))
			form_set_error('isbn3', t('Invalid ISBN for Book Preference 3'));

return;
}*/


function cheque_contct_form()
{
	global $user;
        
	/*$preference4_q = db_query("SELECT id FROM {textbook_companion_proposal} WHERE uid=".$user->uid);*/

	$query = db_select('textbook_companion_proposal');
	$query->fields('id', array(''));
	$query->condition('uid', $user->uid);
	$result = $query->execute();
	$data = $result->fetchObject();

	$form1 = $data->id; 
	
	if($user->uid)
    {
		$form['#redirect'] = FALSE;
		
		$form['search'] = array(
			'#type' => 'textfield',
			'#title' => t('Search'),
			'#size' => 48,
		);
		
		$form['submit'] = array(
			'#type' => 'submit',
			'#value' => t('Search')
		);

		$form['cancel'] = array(
			'#type' => 'markup',
			'#value' => l(t('Cancel'), ''),
		);

		$form['submit2'] = array(
			'#type' => 'markup',
			'#value' => l(t('Generate Report'), 'cheque_contct/report'),
			'#attributes' => array('id' => 'perm_report'),
		);

		/*$search_q = db_query("SELECT * FROM textbook_companion_proposal p,textbook_companion_cheque c WHERE c.address_con = 'Submitted' AND (p.id = c.proposal_id)");*/
		$query = db_select('textbook_companion_proposal', 'p');
		$query->join('textbook_companion_cheque','c','p.id = c.proposal_id');
		$query->fields('p',array('textbook_companion_proposal'));
		$query->fields('c',array('textbook_companion_cheque'));
		$query->condition('c.address_con','Submitted');
		$result = $query->execute();
		
		while ($search_data = $result->fetchObject())
    	{
			$search_rows[] = array(l($search_data->full_name, 'cheque_contct/status/' . $search_data->proposal_id),$search_data->address_con,$search_data->cheque_no,$search_data->cheque_dispatch_date); 
		}
		if ($search_rows)
		{
			$search_header = array('Name Of The Student', 'Application Form Status', 'Cheque No', 'Cheque Clearance Date');
			$output .= theme('table',array('headers'=>$search_header, 'rows'=>$search_rows));
			$form['search_results'] = array(
				'#type' => 'item',
				'#title' => $_POST['search'] ,
				'#markup' => $output,
			);
		}
		else
		{
			$form['search_results'] = array(
				'#type' => 'item',
				'#title' => t('Search results for "') . $_POST['search'] . '"',
				'#markup' => 'No results found',
			);
		}
		if ($_POST)
	    {
			$output = '';
			$search_rows = array();
			$search_quert = '';

			/*$search_q = db_query("SELECT * FROM textbook_companion_proposal p,textbook_companion_cheque c WHERE c.address_con = 'Submitted' AND (p.id = c.proposal_id) AND (p.full_name LIKE '%%%s%%')", $_POST['search']);*/
			$query = db_select('textbook_companion_proposal', 'p');
			$query->join('textbook_companion_cheque','c','p.id = c.proposal_id');
			$query->fields('p',array('textbook_companion_proposal'));
			$query->fields('c',array('textbook_companion_cheque'));
			$query->condition('c.address_con', 'Submitted');
			$query->condition('p.full_name', '%%'.$_POST['search'].'%%', 'LIKE');
			$result = $query->execute();


			while ($search_data = $result->fetchObject())
			{
				$search_rows[] = array(l($search_data->full_name, 'cheque_contct/status/' . $search_data->proposal_id),$search_data->address_con,$search_data->cheque_no,$search_data->cheque_dispatch_date); 
    		}
			if ($search_rows)
    		{	
		    		$search_header = array('Name Of The Student', 'Application Form Status', 'Cheque No', 'Cheque Clearance Date');
		    		$output .= theme('table',array('headers'=>$search_header,'rows'=>$search_rows));
		    		$form['search_results'] = array(
						'#type' => 'item',
						'#title' => t('Search results for "') . $_POST['search'] . '"',
						'#markup' => $output,
					);
			}
			else
			{
					$form['search_results'] = array(
						'#type' => 'item',
						'#title' => t('Search results for "') . $_POST['search'] . '"',
						'#markup' => 'No results found',
					);
			}
  		}
  return $form;
	}
	else
	{
		/*$preference5_q = db_query("SELECT * FROM {textbook_companion_paper} WHERE proposal_id=".$form1);
		$data1 = db_fetch_object($preference5_q);*/
		$query = db_select('textbook_companion_paper');
		$query->fields('textbook_companion_paper');
		$query->condition('proposal_id', $form1);
		$result = $query->execute();
		$data1=$result->fetchObject();

		$form2 = $data1->internship_form;
		$form3 = $data1->copyright_form;
		$form4 = $data1->undertaking_form;
		$form5 = $data1->reciept_form;

		/*$chq_q = db_query("SELECT * FROM {textbook_companion_proposal} WHERE id=".$form1);
		$data_chq = db_fetch_object($chq_q);*/

		$query = db_select('textbook_companion_proposal');
		$query->fields('textbook_companion_proposal');
		$query->condition('id', $form1);
		$result = $query->execute();
		$data_chq=$result->fetchObject();

		$form9 = $data_chq->full_name;
		$form8 = $data->how_project;
		$form10 = $data_chq->mobile;
		$form11 = $data_chq->course;
		$form12 = $data_chq->branch;
		$form13 = $data_chq->university;
		if($form2&&$form3&&$form4&&$form5)
		{
			$form['full_name'] = array(
				'#type' => 'textfield',
				'#title' => t('Full Name'),
				'#size' => 30,
				'#maxlength' => 50,
				'#default_value' => $form9,
			);
			$form['mobile'] = array(
				'#type' => 'textfield',
				'#title' => t('Mobile No.'),
				'#size' => 30,
				'#maxlength' => 15,
				'#default_value' => $form10,
			);
			$form['how_project'] = array(
				'#type' => 'select',
				'#title' => t('How did you come to know about this project'),
				'#options' => array('eSim Website' => 'eSim Website',
					'Friend' => 'Friend',
					'Professor/Teacher' => 'Professor/Teacher',
					'Mailing List' => 'Mailing List',
					'Poster in my/other college' => 'Poster in my/other college',
					'Others' => 'Others'),
				'#default_value' => $form8,
			);
			$form['course'] = array(
				'#type' => 'textfield',
				'#title' => t('Course'),
				'#size' => 30,
				'#maxlength' => 50,
				'#default_value' => $form11,
			);
			$form['branch'] = array(
				'#type' => 'select',
				'#title' => t('Department/Branch'),
				'#options' => array('Electrical Engineering' => 'Electrical Engineering',
                        'Electronics Engineering' => 'Electronics Engineering',
                        'Computer Engineering' => 'Computer Engineering',
                        'Chemical Engineering' => 'Chemical Engineering',
                        'Instrumentation Engineering' => 'Instrumentation Engineering',
                        'Mechanical Engineering' => 'Mechanical Engineering',
                        'Civil Engineering' => 'Civil Engineering',
                        'Physics' => 'Physics',
                        'Mathematics' => 'Mathematics',
                        'Others' => 'Others'),
   				'#default_value' => $form12,
   			);

			$form['university'] = array(
				'#type' => 'textfield',
				'#title' => t('University/Institute'),
				'#size' => 30,
				'#maxlength' => 100,
				'#default_value' => $form13,
			);
			$form['addressforcheque'] = array(
				'#type' => 'textfield',
				'#title' => t('Address For Mailing Cheque'),
				//'#required' => TRUE,
				'#size' => 30,
				'#maxlength' => 100,
			);
			$form['submit'] = array(
				'#type' => 'submit',
				'#value' => t('Submit')
			);
			$form['cancel'] = array(
				'#type' => 'markup',
				'#value' => t('Cancel')
			);
		}
		if (!$form2)
		{ 
			drupal_set_message(t('Internship Form has not been recieved.'), 'error');
		}
		if(!$form3)
		{ 
			drupal_set_message(t('Copyright Form has not been recieved.'), 'error');
		}
		if(!$form4)
		{ 
			drupal_set_message(t('Undertaking Form has not been recieved.'), 'error');
		}
	return $form;
	}
}


/*function cheque_status_form_validate($form, &$form_state) 
{

		//var_dump(($form_state['values']['stu_cheque_details']['cheque_amt']));
		//die;
		if((!$form_state['values']['cheque_no'])||(!is_numeric($form_state['values']['cheque_no'])))
		{
			form_set_error('cheque_no', t('Invalid Student Cheque No. Please enter a valid cheque no.'));
			return;
		}
		if((!$form_state['values']['cheq_amt'])||(!is_numeric($form_state['values']['cheq_amt'])))
		{
			form_set_error('cheq_amt', t('Invalid Student Amount. Please enter a valid numeric amount.'));
			return;
		}
		if((!$form_state['values']['cheque_no_t'])||(!is_numeric($form_state['values']['cheque_no_t'])))
		{
			form_set_error('cheque_no_t', t('Invalid Teacher Cheque No. Please enter a valid cheque no.'));
			return;
		}
		if((!$form_state['values']['cheq_amt_t'])||(!is_numeric($form_state['values']['cheq_amt_t'])))
		{
			form_set_error('cheq_amt_t', t('Invalid Teacher Amount. Please enter a valid numeric amount.'));
			return;
		}
}*/
function MySQL_NOW(){ return date('Y-m-d'); }

function contact_details($form_state)
{
	global $user;
	if(!isset($_REQUEST['msg']))
		drupal_set_message('<strong>Caution</strong>:Please update Contact Detail carefully as this will be used for future reference during <strong>Payment</strong></li></ul>', 'error');

	$x = $user->uid;
        /*$query2 = db_query("SELECT * FROM {textbook_companion_proposal} WHERE uid=".$x);
        $data2 = db_fetch_object($query2);*/

	$query = db_select('textbook_companion_proposal');
	$query->fields('textbook_companion_proposal');
	$query->condition('uid', $x);
	$result = $query->execute();
	$data2=$result->fetchObject();


	if(!$data2)
	{
		drupal_set_message('Fill Up The <a href="proposal">Book Proposal Form</a>', 'error');
		return '';
	}	
	/*$query3 = db_query("SELECT * FROM {textbook_companion_preference} WHERE approval_status=1 AND proposal_id=".$data2->id);
	$data3 = db_fetch_object($query3);*/

	$query = db_select('textbook_companion_preference');
	$query->fields('textbook_companion_preference');
	$query->condition('approval_status', 1);
	$query->condition('proposal_id', $data2->id);
	$result = $query->execute();
	$data3=$result->fetchObject();
	
	if(!$data3->approval_status)
	{
		drupal_set_message('Book Proposal Has Not Been Accpeted .', 'error');
		return '';
	}
        
	$proposal_id = $data2->id;
	
	/*$comment_qx = db_query("SELECT * FROM textbook_companion_cheque c WHERE proposal_id =".$proposal_id);
	$commentv = db_fetch_object($comment_qx);*/

	$query = db_select('textbook_companion_cheque', 'c');
	$query->fields('c');
	$query->condition('proposal_id', $proposal_id);
	$result = $query->execute();
	$commentv=$result->fetchObject();

	$form16 = $commentv->commentf;	
	$mob_no = $data2->mobile;
	$full_name = $data2->full_name;

	/*$query1 = db_query("SELECT * FROM {textbook_companion_cheque} WHERE proposal_id=".$proposal_id);*/
	$query = db_select('textbook_companion_cheque');
	$query->fields('textbook_companion_cheque');
	$query->condition('proposal_id', $proposal_id);
	$result = $query->execute();

	$form1 = 0;
	$form2 = 0;
	$form3 = 0;
	$form4 = 0;
	$form5 = 0;
	$form6 = 0;
	$form7 = 0;
	$form8 = 0;
	$form9 = 0;
	$form10 = 0;
	$form11 = 0;
	$form12 = 0;
	$form13 = 0;
	$form14 = 0;
	$form15 = 0;	
	
	if($data = $result->fetchObject())
	{
		$form1 = $data->address;
		$form8 = $data->alt_mobno;
	 	$form9 = $data->perm_city;
		$form10 = $data->perm_state;
		$form11 = $data->perm_pincode;
 		$form12 = $data->temp_chq_address;
		$form13 = $data->temp_city;
		$form14 = $data->temp_state;
 		$form15 = $data->temp_pincode;
  	}	
  	else
  	{
		/*db_query("insert into {textbook_companion_cheque} (proposal_id) values(%d)",$proposal_id);*/

		$query = "insert into {textbook_companion_cheque} (proposal_id) values (:proposal_id)";
		$args = array(
		":proposal_id"=>$proposal_id, 
			);
		$result = db_query($query, $args, array('return' => Database::RETURN_INSERT_ID));
	}
	$form['candidate_detail'] = array(
	     '#type' => 'fieldset',
             '#value' => $form_html,
	     '#title' => t('Candidate Detail'),
             '#attributes' => array('id' => 'candidate_detail'),
	);
	$form['proposal_id'] =array(
	    '#type' => 'hidden',
	    '#default_value' => $proposal_id,
  	);
	$form['candidate_detail']['fullname'] = array(
            '#type' => 'textfield',
	    '#title' => t('Full Name'),
	    '#size' => 48,
            '#default_value' => $full_name,
  	);
        $form['candidate_detail']['email'] = array(
            '#type' => 'textfield',
	    '#title' => t('Email'),
	    '#size' => 48,
            '#value' => $user->mail,
	    '#disabled' => TRUE,
  	);
	$form['candidate_detail']['mobileno1'] = array(
            '#type' => 'textfield',
	    '#title' => t('Mobile No'),
	    '#size' => 48,
            '#default_value' => $mob_no,
  	);
	
        $form['candidate_detail']['mobileno2'] = array(
            '#type' => 'textfield',
	    '#title' => t('Alternate Mobile No'),
	    '#size' => 48,
            '#default_value' => $form8,
  	);

	/*$chq_q=db_query("SELECT * FROM {textbook_companion_cheque} WHERE proposal_id=".$proposal_id);
        $chq_data=db_fetch_object($chq_q);*/

	$query = db_select('textbook_companion_cheque');
	$query->fields('textbook_companion_cheque');
	$query->condition('proposal_id', $proposal_id);
	$result = $query->execute();
	$chq_data=$result->fetchObject();
 
 
        /*$q_form = db_query("SELECT * FROM {textbook_companion_paper} WHERE proposal_id=".$proposal_id);
        $q_data = db_fetch_object($q_form);*/

	$query = db_select('textbook_companion_paper');
	$query->fields('textbook_companion_paper');
	$query->condition('proposal_id', $proposal_id);
	$result = $query->execute();
	$q_data=$result->fetchObject();

  	$form_html .= '<ul>';
  	if($q_data->internship_form)
	{
		$form_html .= '<li><strong>Internship Application </strong> Form Submitted</li>';
	}
	else
	{
		$form_html .= '<li><strong>Internship Application </strong> Form Not Submitted.<br>Please submit it as soon as possible.</li>';
	}
	if($q_data->copyright_form)
	{
		$form_html .= '<li><strong>Copyright Application </strong> Form Submitted</li>';
	}
	else
	{
		$form_html .= '<li><strong>Copyright Application</strong> Form Not Submitted.<br>Please submit it as soon as possible.</li>';
	}
	if($q_data->undertaking_form)
	{
		$form_html .= '<li><strong>Undertaking Application </strong> Form Submitted</li>';
	}
	else
	{
		$form_html .= '<li><strong>Undertaking Application</strong> Form Not Submitted.<br>Please submit it as soon as possible.</li>';
	}
	$form_html .= '</ul>';
	$form['Application Status'] = array(
	     '#type' => 'fieldset',
             '#value' => $form_html,
	     '#title' => t('Application Form Status'),
             '#attributes' => array('id' => 'app_status'),
	);
        $form['perm_cheque_address'] = array(
    	    '#type' => 'fieldset',
            '#title' => t('Permanent Address'),
            '#collapsible' => TRUE,
            '#collapsed' => FALSE,
            '#attributes' => array('id' => 'perm_cheque_address'),
  	);
	$form['temp_cheque_address'] = array(
	    '#type' => 'fieldset',
            '#title' => t('Temporary Address'),
            '#collapsible' => TRUE,
            '#collapsed' => FALSE,
            '#attributes' => array('id' => 'temp_cheque_address'),
	);
  	$form['perm_cheque_address']['chq_address'] = array(
		'#type' => 'textarea',
		'#title' => t('Address'),
		'#size' => 35,
		'#default_value' => $form1,
	);
	$form['perm_cheque_address']['perm_city'] = array(
		'#type' => 'textfield',
		'#default_value' => $form9,
		'#title' => t('City'),
		'#size' => 35,
	);
	$form['perm_cheque_address']['perm_state'] = array(
		'#type' => 'textfield',
		'#default_value' => $form10,
		'#title' => t('State'),
		'#size' => 35,
	);
	$form['perm_cheque_address']['perm_pincode'] = array(
		'#type' => 'textfield',
		'#default_value' => $form11,
		'#title' => t('Zip code'),
		'#size' => 35,
	);
	$form['temp_cheque_address']['temp_chq_address'] = array(
		'#type' => 'textarea',
		'#default_value' => $form12,
		'#title' => t('Address'),
		'#size' => 35,
	);
	$form['temp_cheque_address']['temp_city'] = array(
		'#type' => 'textfield',
		'#default_value' => $form13,
		'#title' => t('City'),
		'#size' => 35,
	);
	$form['temp_cheque_address']['temp_state'] = array(
		'#type' => 'textfield',
		'#default_value' => $form14,
		'#title' => t('State'),
		'#size' => 35,
	);
	$form['temp_cheque_address']['temp_pincode'] = array(
		'#type' => 'textfield',
		'#default_value' => $form15,
		'#title' => t('Zip code'),
		'#size' => 35,
	);
	$form['temp_cheque_address']['same_address'] = array(
		'#type' => 'checkbox',
		'#title' => t('Same As Permanent Address'),
	);
	if($chq_data->commentf)
	{     
		$form['commentu'] = array(
	        	'#type' => 'fieldset',
  	        	'#title' => t('Remarks'),
 			'#collapsible' => FALSE,
 			'#collapsed' => FALSE,
                	'#attributes' => array('id' => 'comment_cheque'),
    		);
	        $form['commentu']['comment_cheque'] = array(
	        	'#type' => 'textarea',
			'#size' => 35,  
	        	'#default_value' => $form16,
		);
	}
	$form['commentu']['comment_cheque'] ['#attributes']['readonly'] = 'readonly';
	$form['submit'] = array(
    		'#type' => 'submit',
		'#value' => t('Update')
    	);
    	$form['cancel'] = array(
		'#type' => 'markup',
		'#value' => l(t('Cancel'), 'manage_proposal/all'),
	);
    return $form;       
}
function contact_details_submit($form, &$form_state)
{

	global $user;
	$x = $user->uid;

	/*$query2 = db_query("SELECT * FROM {textbook_companion_proposal} WHERE uid=".$x);
	$data2 = db_fetch_object($query2);*/

	$query = db_select('textbook_companion_proposal');
	$query->fields('textbook_companion_proposal');
	$query->condition('uid', $x);
	$result = $query->execute();
	$data2=$result->fetchObject();


	/*$query ="UPDATE {textbook_companion_cheque} SET 
	alt_mobno = '".$form_state['values']['mobileno2']."' , 
	address = '".$form_state['values']['chq_address']."',  
	perm_city = '".$form_state['values']['perm_city']."', 
	perm_state = '".$form_state['values']['perm_state']."', 
	perm_pincode = '".$form_state['values']['perm_pincode']."', 
	temp_chq_address = '".$form_state['values']['temp_chq_address']."', 
	temp_city = '".$form_state['values']['temp_city']."', 
	temp_state = '".$form_state['values']['temp_state']."', 
	temp_pincode = '".$form_state['values']['temp_pincode']."' ,
	address_con = 'Submitted'
	WHERE proposal_id = ".$data2->id;
	db_query($query);*/

	$query = db_update('textbook_companion_cheque');
	$query->fields(array(
  	'alt_mobno' => $form_state[ values ][ mobileno2 ],
  	'address' => $form_state[ values ][ chq_address ],
  	'perm_city' => $form_state[ values ][ perm_city ],
  	'perm_state' => $form_state[ values ][ perm_state ],
  	'perm_pincode' => $form_state[ values ][ perm_pincode ],
  	'temp_chq_address' => $form_state[ values ][ temp_chq_address ],
  	'temp_city' => $form_state[ values ][ temp_city ],
  	'temp_state' => $form_state[ values ][ temp_state ],
  	'temp_pincode' => $form_state[ values ][ temp_pincode ],
  	'address_con' => 'Submitted',
	));
	$query->condition('proposal_id', $data2->id);
	$num_updated = $query->execute();

	drupal_set_message('Contact Details Has Been Updated.....!', 'status');
	drupal_goto('mycontact', array('msg' => 0 ), $fragment = NULL, $http_response_code = 302);
}
function copy_address()
{
    
    alert("Hello");
    $form['temp_cheque_address']['temp_chq_address'] = array(
    	'#type' => 'textarea',
    	'#default_value' => $form['values']['chq_address'],
    	'#title' => t('Address Street 1'),
    );
    
    $form['temp_cheque_address']['temp_city'] = array(
    	'#type' => 'textfield',
    	'#default_value' => $form['values']['perm_city'],
    	'#title' => t('City'),
    	'#size' => 35,
    );
    $form['temp_cheque_address']['temp_state'] = array(
    	'#type' => 'textfield',
    	'#default_value' => $form['values']['perm_state'],
    	'#title' => t('State'),
    	'#size' => 35,
    );
    $form['temp_cheque_address']['temp_pincode'] = array(
    	'#type' => 'textfield',
    	'#default_value' => $form['values']['perm_pincode'],
    	'#title' => t('Zip code'),
    	'#size' => 35,
    );
}

?>
