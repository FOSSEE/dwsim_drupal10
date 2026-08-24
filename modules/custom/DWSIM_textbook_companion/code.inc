<?php

/**
 * Legacy upload_examples_form / ajax_chapter_name_callback removed.
 * Active: Drupal\textbook_companion\Form\UploadExamplesForm
 */

/******************************************************************************/
/***************************** DELETE EXAMPLE *********************************/
/******************************************************************************/
function _upload_examples_delete()
{
	global $user;
	$root_path  = textbook_companion_path();
	$example_id = arg(3);
	//var_dump($example_id);die;
	/* check example */
	/*$example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE id = %d LIMIT 1", $example_id);
	$example_data = db_fetch_object($example_q);*/
	$query      = db_select('textbook_companion_example');
	$query->fields('textbook_companion_example');
	$query->condition('id', $example_id);
	$query->range(0, 1);
	$result       = $query->execute();
	$example_data = $result->fetchObject();
	if (!$example_data) {
		drupal_set_message('Invalid example.', 'error');
		drupal_goto('textbook-companion/code');
		return;
	} //!$example_data
	if ($example_data->approval_status != 0) {
		drupal_set_message('You cannnot delete an example after it has been approved. Please contact site administrator if you want to delete this example.', 'error');
		drupal_goto('textbook-companion/code');
		return;
	} //$example_data->approval_status != 0
	/*$chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE id = %d LIMIT 1", $example_data->chapter_id);
	$chapter_data = db_fetch_object($chapter_q);*/
	$query = db_select('textbook_companion_chapter');
	$query->fields('textbook_companion_chapter');
	$query->condition('id', $example_data->chapter_id);
	$query->range(0, 1);
	$result       = $query->execute();
	$chapter_data = $result->fetchObject();
	if (!$chapter_data) {
		drupal_set_message('You do not have permission to delete this example.', 'error');
		drupal_goto('textbook-companion/code');
		return;
	} //!$chapter_data
	/*$preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE id = %d LIMIT 1", $chapter_data->preference_id);
	$preference_data = db_fetch_object($preference_q);*/
	$query = db_select('textbook_companion_preference');
	$query->fields('textbook_companion_preference');
	$query->condition('id', $chapter_data->preference_id);
	$query->range(0, 1);
	$result          = $query->execute();
	$preference_data = $result->fetchObject();
	if (!$preference_data) {
		drupal_set_message('You do not have permission to delete this example.', 'error');
		drupal_goto('textbook-companion/code');
		return;
	} //!$preference_data
	/*$proposal_q = db_query("SELECT * FROM {textbook_companion_proposal} WHERE id = %d AND uid = %d LIMIT 1", $preference_data->proposal_id, $user->uid);
	$proposal_data = db_fetch_object($proposal_q);*/
	$query = db_select('textbook_companion_proposal');
	$query->fields('textbook_companion_proposal');
	$query->condition('id', $preference_data->proposal_id);
	$query->condition('uid', $user->uid);
	$query->range(0, 1);
	$result        = $query->execute();
	$proposal_data = $result->fetchObject();
	if (!$proposal_data) {
		drupal_set_message('You do not have permission to delete this example.', 'error');
		drupal_goto('textbook-companion/code');
		return;
	} //!$proposal_data
	/* deleting example files */
	if (delete_example($example_data->id)) {
		drupal_set_message('Example deleted.', 'status');
		/* sending email */
		$email_to = $user->mail;
		$from = variable_get('textbook_companion_from_email', '');
		$bcc = variable_get('textbook_companion_emails', '');
		$cc = variable_get('textbook_companion_cc_emails', '');
		$params['example_deleted_user']['book_title'] = $preference_data->book;
        $params['example_deleted_user']['chapter_title'] = $chapter_data->name;
        $params['example_deleted_user']['example_number'] = $example_data->number;
        $params['example_deleted_user']['example_caption'] = $example_data->caption;
        $params['example_deleted_user']['user_id'] = $user->uid;
		$params['example_deleted_user']['headers'] = array(
		'From' => $from,
		'MIME-Version' => '1.0',
		'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
		'Content-Transfer-Encoding' => '8Bit',
		'X-Mailer' => 'Drupal',
		'Cc' => $cc,
		'Bcc' => $bcc
	);
        	if (!drupal_mail('textbook_companion', 'example_deleted_user', $email_to, language_default(), $params, $from, TRUE))
            		drupal_set_message('Error sending email message.', 'error');
	} //delete_example($example_data->id)
	else {
		drupal_set_message('Error deleting example.', 'status');
	}
	drupal_goto('textbook-companion/code');
	return;
}
/******************************************************************************/
/************************** GENERAL FUNCTIONS *********************************/
/******************************************************************************/
function _list_of_book_titles()
{
	$book_titles = array(
		'0' => 'Please select...'
	);
	/*$book_titles_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE approval_status = 1 OR approval_status = 3 ORDER BY book ASC");*/
	$query       = db_select('textbook_companion_preference');
	$query->fields('textbook_companion_preference');
	$or = db_or();
	$or->condition('approval_status', 1);
	$or->condition('approval_status', 3);
	$query->condition($or);
	$query->orderBy('book', 'ASC');
	$book_titles_q = $query->execute();
	while ($book_titles_data = $book_titles_q->fetchObject()) {
		$book_titles[$book_titles_data->id] = $book_titles_data->book . ' (Written by ' . $book_titles_data->author . ')';
	} //$book_titles_data = $book_titles_q->fetchObject()
	return $book_titles;
}
function _list_of_book_dependency_files()
{
	$book_dependency_files       = array();
	$book_dependency_files_class = array();
	/*$book_dependency_files_q = db_query("SELECT * FROM {textbook_companion_dependency_files} ORDER BY filename ASC");*/
	$query                       = db_select('textbook_companion_dependency_files');
	$query->fields('textbook_companion_dependency_files');
	$query->orderBy('filename', 'ASC');
	$book_dependency_files_q = $query->execute();
	while ($book_dependency_files_data = $book_dependency_files_q->fetchObject()) {
		$temp_caption = '';
		if ($book_dependency_files_data->caption)
			$temp_caption .= ' (' . $book_dependency_files_data->caption . ')';
		$book_dependency_files[$book_dependency_files_data->id]       = l($book_dependency_files_data->filename . $temp_caption, 'download/dependency/' . $book_dependency_files_data->id, array(
			'attributes' => array(
				'class' => $book_dependency_files_data->preference_id
			)
		));
		$book_dependency_files_class[$book_dependency_files_data->id] = $book_dependency_files_data->preference_id;
	} //$book_dependency_files_data = $book_dependency_files_q->fetchObject()
	return array(
		$book_dependency_files,
		$book_dependency_files_class
	);
}

