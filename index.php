<?php
///*
/*
_________ _______           ______   _______  _______          _________
\__   __/(  ____ )|\     /|(  ___ \ (  ____ \(  ___  )|\     /|\__   __/|\     /|
  ) (   | (    )|| )   ( || (   ) )| (    \/| (   ) || )   ( |   ) (   ( \   / )
  | |   | (____)|| |   | || (__/ / | (__    | (___) || |   | |   | |    \ (_) /
  | |   |     __)| |   | ||  __ (  |  __)   |  ___  || |   | |   | |     \   /
  | |   | (\ (   | |   | || (  \ \ | (      | (   ) || |   | |   | |      ) (
  | |   | ) \ \__| (___) || )___) )| (____/\| )   ( || (___) |   | |      | |
  )_(   |/   \__/(_______)|/ \___/ (_______/|/     \|(_______)   )_(      \_/

  Author: Jason Townes French (jtfrench@gmail.com)
  (c) 2015. Dina Burkitbayeva

 */
require('../include/libTB.php');
$cmd = $_GET['cmd'];
if(!isset($cmd)){
    $cmd = 'adminDashboard';
}
switch ($cmd) {

    case 'login':
        $v = new TBAdminLoginView();
        $v->render();
        break;

    case 'logout':
        session_destroy();
        header('Location: ?cmd=login');
        break;

    case 'loginAdminUser':
        $email = $_POST['email'];
        $pass = $_POST['password'];
        $response = array();
        if(login($email, $pass)){
            $response['status'] = 'success';
            $response['session'] = $_SESSION;
        }
        else{
            $response['status'] = 'failure';
            $response['message'] = 'authentication failed';
        }
        error_log('Login Session: ' . print_r($_SESSION, true) );
        header('Location: ?cmd=adminDashboard');
        break;

    case 'sendApplePushNotification':
        $deviceToken = $_POST['deviceToken'];
        $message = $_POST['message'];
        $url = $_POST['url'];
        $category = $_POST['category'];

        $result = TBNotification::sendPushNotification($deviceToken, $message, $url, $category);
        $response = array();
        if($result){
            $response['status'] = 'success';
        }
        else{
            $response['status'] = 'failure';
        }
        
        header('Content-Type: application/json');
        echo json_encode($response);
        break;

    case 'applePushNotifications':
        $v = new TBAdminApplePushNotificationsView();
        $v->render();
        break;

    case 'requestBooking':
        $v = new TBAdminRequestBookingView();
        $v->render();
        break;

    case 'getSalonData':
        $v = new TBAdminGetSalonDataView();
        $v->render();
        break;

    case 'messagePopups':
        $v = new TBAdminMessagePopupsView();
        $v->render();
        break;

    case 'animationPopups':
        $v = new TBAdminAnimationPopupsView();
        $v->render();
        break;

    case 'rebuildSheetCache':
        TBGoogle::rebuildCache();
        break;

    case 'migrateCode':
        var_dump($_POST);

        break;

    case 'processImages':
        $directory = DOCUMENT_ROOT . '/images/';
        echo '<h1>Image Processor v' . PRODUCT_VERSION . '</h1>';
        $files = glob($directory . '*');
        echo 'Processing ' . count($files) . ' files...<br />

        <ol>';
        $sql = "SELECT * FROM tb_salons";
        $result = mysql_query($sql) or die(mysql_error());
        $num = mysql_num_rows($result);
        $salons = array();
        $salon_name_lookup = array();
        for($i= 0; $i < $num; $i++)
        {
            $arr = mysql_fetch_assoc($result);
            array_push($salons, $arr);
            $key = trim(strtolower(str_replace(' ', '',$arr['name'])));
           // $salon_name_lookup[$key] = $arr;
        }
        foreach( $files as $filename){
            $filename = str_replace($directory,'',$filename);
            $toks = explode('.',$filename);
            echo '<li>' . $filename . '<br />' ;

            // Process file name and calculate new name
            $oldName = $directory . $filename;
            $key = trim(strtolower(str_replace(' ', '',$toks[0])));

            foreach($salons as $salon)
            {

                $candidate = strtolower(str_replace('_',' ', $salon['name']));
                $haystack = strtolower(str_replace('_', ' ', $toks[0]));

                //echo 'Does ' . $haystack .' contain ' . $candidate .' ?<br />';
                $pos = strpos($haystack, $candidate);
                if ($pos !== FALSE && $pos == 0)
                {
                    echo '<span style = "color:green">Found <strong>' . $candidate . '</strong> in <strong>' . $haystack . '</strong>!<ul>';
                    if(!isset($salon_name_lookup[$candidate]))
                    {
                        $salon_name_lookup[$candidate] = 0;
                    }
                    $index = $salon_name_lookup[$candidate];
                    echo '<li>' . $filename . ' matches ' . $salon['name'] . '</li>';
                    echo '<li>Photo Index: ' . $index . '</li>';
                    echo '<li>Position Index: ' . $pos . '</li>';

                    $assetName = 's_' . $salon['id']  . '_i_' . $index . '.' . $toks[1];
                    $newName = $directory . $assetName;

                    if (strpos($newName, '.') !== FALSE)
                    {

                    }
                    else
                    {
                        $newName = $newName . '.png';
                    }

                    echo 'Renaming from ' . $oldName . ' to ' . $newName . '<br />';
                    $success = rename($oldName, $newName);

                    if(!$success)
                    {
                        echo '<br /><span style = "color:red">Failed to rename file</span>';
                    }
                    else
                    {
                        echo '<br /><span style = "color:green">Successfully renamed file from ' . $oldName . ' to ' . $newName . '</span>';
                    }
                    echo '</ul>';
                    echo '
                    </span>';

                    $salon_name_lookup[$candidate] = $index + 1;
                    break;

                }
                else
                {
                    //echo "nope!";
                }
            }

            /*

            $assetName = $filename . '_PROCESSED.' . $toks[1];
            $newName = $directory . $assetName;


            echo 'Renaming from ' . $oldName . ' to ' . $newName . '<br />';
            $success = rename($oldName, $newName);

            if(!$success)
            {
                echo '<br /><span style = "color:red">Failed to rename file</span>';
            }
            else
            {
                echo '<br /><span style = "color:green">Successfullyrenamed file</span>';
            }
            */

            echo '</li>';

        }
        echo '</ol>';
        break;

    case 'importSalonProfiles':
        TBDataImporter::importSalonProfiles();
        break;

    case 'processUploadedImages':
        TBImage::processUploadedImages();
        break;

    case 'importPilotData':
        TBDataImporter::import();
        break;

    case 'cleanDatabase':
        TBDataImporter::cleanDatabase();
        break;

    case 'browseUsers':
        $v = new TBAdminUserTableView();
        $v->render();
        break;

    case 'viewUser':
		$userId = $_GET['userId'];
        $v = new TBAdminUserView($userId);
        $v->render();
        break;

    case 'adminTest':
        echo 'Started test';
        TBSalon::getAllSalons(true);
        break;

    case 'adminTest2':
        echo 'Started test 2';
        TBImage::getImagesBySalonId(109);
        break;

    case 'adminTest3':
        echo 'Started test 3';
        TBService::getServicesAtSalon(109);
        echo 'Finished test 3';
        break;

    case 'browseSalons':
        $v = new TBAdminSalonTableView();
        $v->render();
        break;

    case 'addSalon':
		if( isset( $_POST['submit']) && $_POST['submit'] != ""){
			$result = TBSalon::addSalon($_POST);
			if($result){
				header("Location:?cmd=browseSalons");
			}
		}
        $v = new TBAdminSalonAddView();
        $v->render();
        break;

    case 'editSalon':
		$salonId = $_GET['salonId'];
		$msg = "";
        if( isset( $_POST['submit']) && $_POST['submit'] != ""){
			 $success = TBSalon::editSalon( $salonId,$_POST );
		}
		if($success){
			$msg = "Details Updated";
		}
        $v = new TBAdminSalonEditView($salonId, $msg);
        $v->render();
        break;

    case 'deleteSalon':
		$salonId = $_GET['salonId'];
        $success = TBSalon::deletesalon( $salonId );
		break;

    case 'browseSearchTags':
        $v = new TBAdminSearchTagTableView();
        $v->render();
        break;

	 case 'addSearchTag':
		if( isset( $_POST['submit']) && $_POST['submit'] != ""){
			$success = TBSearch::addSearchTag($_POST);
		}
		if($success){
			header('Location: ?cmd=browseSearchTags');
		}
        $v = new TBAdminSearchTagAddView();
        $v->render();
        break;

	case 'deleteSearchTag':
		$searchTagId = $_GET['searchTagId'];
		$success = TBSearch::deleteSearchTag( $searchTagId );
        break;

	case 'dataMigration':
        $v = new TBAdminDataMigrationView();
        $v->render();
        break;

    case 'exportSalonPatch':
        $fileUrl = TBDataImporter::exportSalonPatch();
        $results = '<strong>Salon Patch Download Link:</strong><ul><li><a href = "' . $fileUrl . '" title = "Salon Patch Download Link">' . $fileUrl . '</a></li></ul>';
        $v = new TBAdminDataMigrationResultsView($results);
        $v->render();
        break;

    case 'uploadSalonPatch':
        $results = '<ul>';
        $target_dir = DOCUMENT_ROOT . "/admin/patches/uploaded/";
        $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
        $uploadOk = 1;
        $patchFileType = pathinfo($target_file,PATHINFO_EXTENSION);

        // Check file size
        if ($_FILES["fileToUpload"]["size"] < 20) {
            $results .= '<li>Sorry, your file is too small.</li>';
            $uploadOk = 0;
        }

        // Allow certain file formats
        if($patchFileType != "json" && $patchFileType != "txt" ) {
            $results .= "<li>Only JSON or TXT are allowed.</li>";
            $uploadOk = 0;
        }

        // Check if $uploadOk is set to 0 by an error
        if ($uploadOk == 0) {
            $results .= "<li><em>Your patch file could not be processed.</em></li>";
            // if everything is ok, try to upload file
        } else {
            if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
                $results .= "<li>The file ". basename( $_FILES["fileToUpload"]["name"]). " has been uploaded.</li>";
                $json = file_get_contents($target_file);
                TBDataImporter::importSalonPatch($json);
                $results .= '<li>Successfully processed salon patch</li>';
            } else {
                $results .= "<li>Sorry, there was an error uploading your file.</li>";
            }
        }

        $results .= '</ul>';
        $v = new TBAdminDataMigrationResultsView($results);
        $v->render();
        break;

    case 'viewSalon':
        $salonId = $_GET['salonId'];
        $v = new TBAdminSalonView($salonId);
        $v->render();
        break;
	
    case 'salonServicesOptions':
        $salonId = $_GET['salonId'];
        $v = new TBAdminsalonServicesOptionsView($salonId);
        $v->render();
        break;
	
    case 'hijackSalon':
        $salonId = sanitize($_GET['salonId']);
        if(true){//TODO: secure this
            $sql = "SELECT * FROM tb_salonregistrations AS s_r JOIN tb_salons AS s ON s.id = s_r.salonId WHERE s_r.salonId ='$salonId'";
            error_log('HIJACK LOOKUP SQL: ' . $sql);
            $result = mysql_query($sql) or die(mysql_error());
            $arr = mysql_fetch_assoc($result);
            $salonAdminUserId = intval($arr['userId']);
            if($salonAdminUserId > 0){
                header('Location: /salonadmin/?cmd=dashboard');
            }
            die('No salon admin account exists yet for this salon. It must\'ve been created during an Importer run.');

        }
        break;

    case 'setReferralCodeForSalon':
        $salonId = $_POST['salonId'];
        $code = $_POST['code'];
        $response = array();
        if(TBReferralCode::setReferralCodeForSalon($code, $salonId)){
            $response['status'] = 'success';
        }else{
            $response['status'] = 'failure';
        }
        header('Content-Type: application/json');
        echo json_encode($response);
        break;

    case 'lockAllSearchTags':
        $salonId = $_POST['salonId'];
        $response = array();
        TBSearch::lockAllSearchTags($salonId);
        $response['status'] = 'success';
        header('Content-Type: application/json');
        echo json_encode($response);
        break;

    case 'unlockAllSearchTags':
        $salonId = $_POST['salonId'];
        $response = array();
        TBSearch::unlockAllSearchTags($salonId);
        $response['status'] = 'success';
        header('Content-Type: application/json');
        echo json_encode($response);
        break;

    case 'updateExport':
        $salonId = intval($_POST['salonId']);
        if($salonId == 0){
            $response = array();
            $response['message'] = 'Null salon id';
            header('Content-Type: application/json');
            echo json_encode($response);
            break;
        }
        $exportReady = $_POST['exportReady'];
        error_log('updateExport');
        error_log(print_r($_POST, true));
        $response = array();
        error_log('Export ready: ' . $exportReady);
        if($exportReady == 'true'){
            $sql = "UPDATE tb_salons SET exportReady = 'yes' WHERE id = '$salonId'";
            $response['message'] = 'Salon #' . $salonId . ' marked ready for export';
        }
        else{
            $sql = "UPDATE tb_salons SET exportReady = 'no' WHERE id = '$salonId'";
            $response['message'] = 'Marked Salon #' . $salonId . ' NOT ready for export';
        }
        error_log('SQL: ' . $sql);
        $result = mysql_query($sql) or die(mysql_error());
        header('Content-Type: application/json');
        echo json_encode($response);
        break;

    case 'lockSearchTag':
        $serviceId = intval($_POST['serviceId']);
        if($serviceId == 0){
            $response = array();
            $response['message'] = 'Null service id';
            header('Content-Type: application/json');
            echo json_encode($response);
            break;
        }
        $searchtagsLocked = $_POST['searchtagsLocked'];
        error_log('lockSearchTags');
        error_log(print_r($_POST, true));
        $response = array();
        if($searchtagsLocked == 'true'){
            $sql = "UPDATE tb_services SET searchtagsLocked = 'yes' WHERE id = '$serviceId'";
            $response['message'] = 'Locked search tags for service #' . $serviceId . '.';
            $response['searchtagsLocked'] = true;
        }
        else{
            $sql = "UPDATE tb_services SET searchtagsLocked = 'no' WHERE id = '$serviceId'";
            $response['message'] = 'Unlocked search tags for service #' . $serviceId . '.';
            $response['searchtagsLocked'] = false;
        }
        error_log('SQL: ' . $sql);
        $result = mysql_query($sql) or die(mysql_error());
        header('Content-Type: application/json');
        echo json_encode($response);
        break;

    case 'autoTagService':
        $serviceId = $_POST['serviceId'];
        $response = array();
        if(TBSearch::autoTagService($serviceId)){
            $response['status'] = 'success';
            $response['message'] = 'Successfully auto-tagged Service #' . $serviceId;
        }else{
            $response['status'] = 'failure';
            $response['message'] = 'Couldn\'t auto-tag Service #' . $serviceId;
        }
        header('Content-Type: application/json');
        echo json_encode($response);
        break;

    case 'browseAppointments':
        $v = new TBAdminAppointmentTableView();
        $v->render();
        break;

    case 'editAppointment':
		$apptId = $_GET['apptId'];
		$msg = "";
		if( isset( $_POST['submit']) && $_POST['submit'] != ""){
			 $success = TBAppointment::editAppointment( $apptId,$_POST );
		}
		if($success){
			header("Location:?cmd=browseAppointments");
		}
		$v = new TBAdminAppointmentEditView($apptId);
		$v->render();
        break;

    case 'browseReferralCodes':
        $v = new TBAdminReferralCodeTableView();
        $v->render();
        break;

    case 'browseCustomRequests':
        $v = new TBAdminCustomRequestTableView();
        $v->render();
        break;

    case 'importSalons':
        $salons = TBGoogle::getSpreadsheet('1rfIzCb-4xN4QFYchSmIZIz4N8kOVOBICQZ8e200exk4');
        for ($i = 4; $i < (4 + count($salons)); $i++) {
            echo $salons[$i]['B'];
            $prettyId = strtolower($salons[$i]['A']);
            $name = $salons[$i]['B'];
            $address = $salons[$i]['C'];
            $neighborhood = $salons[$i]['D'];
            $phone = $salons[$i]['E'];
            $hours = $salons[$i]['H'];
            $website = $salons[$i]['I'];
            $yelp = $salons[$i]['K'];
            $description = mysql_real_escape_string($salons[$i]['M']);
            $sql = "SELECT * FROM tb_salons WHERE prettyId = '$prettyId'";
            $result = mysql_query($sql) or die(mysql_error());
            $num = mysql_num_rows($result);
            if ($num == 0) {
                echo 'Insert as new salon in internal db...<br />';
                $sql_2 = "INSERT INTO tb_salons (prettyId, name, address, neighborhood, phone, hours, website, yelp, description) VALUES ('$prettyId','$name','$address','$neighborhood','$phone','$hours','$website','$yelp','$description')";
                $result_2 = mysql_query($sql_2) or die(mysql_error());
            } else {
                echo 'Update salon in internal db...<br />';
                $sql_2 = "UPDATE tb_salons SET name = '$name', address = '$address', neighborhood = '$neighborhood', phone = '$phone', hours = '$hours', website = '$website', yelp = '$yelp' , description = '$description' WHERE prettyId = '$prettyId'";
                $result_2 = mysql_query($sql_2) or die(mysql_error());
            }
        }
        echo 'Done importing salons';
        break;

    case 'adminAddSalon':
        $v = new TBAdminAddObjectView('Salon');
        $v->render();
        break;

    case 'adminImportSalons':
        $v = new TBAdminImportSalonsView();
        $v->render();
        break;

    case 'adminAddObject':
        $className = $_POST['className'];
        $objectName = $_POST['name'];
        $data = array();
        $data['name'] = $objectName;
        $obj = TBObject::create($className, $data);

        if ($className == 'Customer') {
            //$className = 'User';
        }
        header('Location: ?cmd=admin' . $className . 's');
        break;

    case 'adminEditObject':
        $className = $_POST['className'];
        $objectName = $_POST['name'];
        $id = $_POST['id'];
        $sql = 'UPDATE tb_' . strtolower($className) . 's SET ';
        foreach ($_POST as $key => $value) {
            if ($key == 'className' || $key == 'id') {
                continue;
            }
            $sql .= "\n" . $key . " = '" . $value . "',";
        }
        $sql = substr($sql, 0, strlen($sql) - 1);
        $sql .= " WHERE id = '$id'";
        mysql_query($sql) or die(mysql_error());
        if ($className == 'User') {
            $className = 'Customer';
        }
        header('Location: ?cmd=admin' . $className . 's');
        break;


    case 'adminAddTechnician':
        $v = new TBAdminAddObjectView('Technician');
        $v->render();
        break;

    case 'adminAddCustomer':
        $v = new TBAdminAddObjectView('Customer');
        $v->render();
        break;

    case 'adminDashboard':
        if($_SESSION['isAdmin'] != 'yes' && $_SESSION['isSalon'] != 'yes'){
            //This is a regular user trying to access admin...kick them out
            header('Location: ?cmd=login');
        }
        $v = null;
        if($_SESSION['isSalon'] == 'yes'){
            $v = new TBSalonAdminDashboardView();
        }
        else{
            $v = new TBAdminDashboardView();
        }

        $v->render();
        break;

    case 'adminEditTechnician':
        $salonId = $_GET['id'];
        $v = new TBAdminEditObjectView('Technician', $salonId);
        $v->render();
        break;

    case 'adminEditCustomer':
        $salonId = $_GET['id'];
        $v = new TBAdminEditObjectView('User', $salonId);
        $v->render();
        break;

    case 'adminEditSalon':
        $salonId = $_GET['id'];
        $v = new TBAdminEditObjectView('Salon', $salonId);
        $v->render();
        break;

    case 'adminSalons':
        echo 'xaveir';
        $v = new TBAdminSalonTableView();
        $v->render();
        break;

    case 'adminTechnicians':
        $v = new TBAdminTechnicianTableView();
        $v->render();
        break;
    /**
     * Flag users that signed up with referral code
     * @param
     * @return
     */

    case 'adminCustomers':
        $v = new TBAdminCustomerTableView();
        $v->render();
        break;

    case 'adminAvailability':
        $v = new TBAdminAvailabilityTableView();
        $v->render();
        break;

    case 'adminActions':
        $v = new TBAdminActionTableView();
        $v->render();
        break;

    case 'adminPromotions':
        $v = new TBAdminPromotionTableView();
        $v->render();
        break;

    case 'adminAnalytics':
        $v = new TBAdminAnalyticsView();
        $v->render();
        break;

    case 'adminReports':
        $v = new TBAdminReportsView();
        $v->render();
        break;

    case 'adminSettings':
        $v = new TBAdminSettingsView();
        $v->render();
        break;

    case 'adminAddSalonFavoriteForUser':
        $v = new TBAddSalonFavoriteForUserTableView('User', $salonId);
        $v->render();
        break;

    /**
     * Automated confirmations of appointments made via text
     * @param
     * @return confirm appoinments view in admin
     */
    case 'adminConfirmappointments':
        $v = new TBAdminConfirmappointmentsTableView();
        $v->render();
        break;

    /**
     * Admin Credit Table view
     * @param
     * @return
     */
    case 'adminCreditInfo':
        $v = new TBAdminCreditTableView();
        $v->render();
        break;

    /**
     * Admin edit Credit Info
     * @param
     * @return
     */
    case 'adminEditCreditInfo':
        $id=isset($_REQUEST['id'])?$_REQUEST['id']:null;
        $v = new TBAdminCreditEditView($id);
        $v->render();
        break;
      /**
     * Admin edit Credit Info
     * @param
     * @return
     */
    case 'adminEditCreditInfoProcess':
        $id=isset($_REQUEST['id'])?$_REQUEST['id']:null;
        $message=isset($_REQUEST['message'])?addslashes($_REQUEST['message']):null;
        $response= TBCredits::updateCreditInfo($id,$message);
        header('Location: ?cmd=adminCreditInfo');
        echo json_encode($response);
        break;

    case 'adminSetSalonDescription':
        $salonId = $_POST['salonId'];
        $desc = $_POST['description'];
        TBSalon::setDescription($desc, $salonId);
        $response = array();
        header('Location: ?cmd=adminCreditInfo');
        echo json_encode($response);
        break;

    case 'adminUploadProfilePhoto':
        error_log('uploadProfilePhoto');
        $target_dir = "images/salonProfiles/";
        $target_file = $target_dir . basename($_FILES["userfile"]["name"]);
        $uploadOk = 1;
        $imageFileType = pathinfo($target_file,PATHINFO_EXTENSION);
        $response = array();
        $response['errors'] = array();
        // Check if image file is a actual image or fake image
        $check = getimagesize($_FILES["userfile"]["tmp_name"]);
        if($check !== false) {
            $mess = "File is an image - " . $check["mime"] . ".";

            $uploadOk = 1;
        } else {
            $mess = "File is not an image.";
            array_push($response['errors'], $mess);
            $uploadOk = 0;
        }

        // Check file size
        $kiloByte = 1024;
        $megaByte = 1024 * $kiloByte;
        $sizeLimit = 10 * $megaByte;
        if ($_FILES["userfile"]["size"] > $sizeLimit) {
            $mess = "Sorry, your file is too large.";
            array_push($response['errors'], $mess);
            $uploadOk = 0;
        }

        // Allow certain file formats
        if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
            && $imageFileType != "gif" ) {
            $mess = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
            array_push($response['errors'], $mess);
            $uploadOk = 0;
        }

        // Check if $uploadOk is set to 0 by an error
        if ($uploadOk == 0) {
            $mess = "Sorry, your file was not uploaded.";
            array_push($response['errors'], $mess);
            // if everything is ok, try to upload file
        } else {
            if (move_uploaded_file($_FILES["userfile"]["tmp_name"], $target_file)) {
                $mess = "The file ". basename( $_FILES["userfile"]["name"]). " has been uploaded.";
            } else {
                $mess = "Sorry, there was an error uploading your file.";
                array_push($response['errors'], $mess);
            }
        }
        if(count($response['errors']) == 0){
            $response['status'] = 'success';
        }
        else
        {
            $response['status'] = 'failure';
        }
        header('Content-Type: application/json');
        echo json_encode($response);
        break;

    case 'importSalonsFromSpreadsheet':

        break;
}
?>
