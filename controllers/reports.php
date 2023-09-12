<?php
//require __DIR__ . '/../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use League\Csv\Reader;
define('FPDF_FONTPATH', __DIR__ . '/../vendor/setasign/fpdf/font/');
require(__DIR__ . '/../vendor/setasign/fpdf/fpdf.php');


class Reports extends Controller{
	
public function __construct(){
parent::__construct();
// Auth::handleSignin();
// Auth::CheckSession();
// Auth::CheckAuthorization();
// $_SESSION['timeout'] = time(); 
error_reporting(E_ALL);
ini_set('display_errors', 1);
}
function index(){

// $this->view->render('reports/dashboard');
echo "AM IN REPORTS";
}

/* Client Listing   */



function MembersReports(){	
$this->view->render('reports/clients_reports');
}



function Members(){	
$this->view->render('reports/clientList_report');
}

function membersList(){
    try {
        $headers = getallheaders();
        $office = $headers['office'];

        $membersListData = $this->model->membersList($office);

        $response = array("status" => 200, "data" => $membersListData);

        header('Content-Type: application/json');
        echo json_encode($response);
    } catch (Exception $e) {
        $errorResponse = array("status" => 500, "message" => $e->getMessage());
        header('Content-Type: application/json');
        http_response_code($errorResponse['status']);
        echo json_encode($errorResponse);
    }
}

function dormantaccount($type)
{
    try {
        $headers = getallheaders();
        $office = $headers['office'];

        if ($type == 'range') {
            $data = $this->model->getDormantMembersListRange($office);
        } else {
            $data = $this->model->getDormantMembersList($office, $type);
        }

        header('Content-Type: application/json');
        http_response_code(200);
        echo json_encode($data);
    } catch (Exception $e) {
        $errorResponse = array("status" => 500, "message" => $e->getMessage());
        header('Content-Type: application/json');
        http_response_code($errorResponse['status']);
        echo json_encode($errorResponse);
    }
}

function wallets(){
   $this->view->wallets = $this->model->getWallets();
   // $this->view->wallets_list = $this->model->getWallets();
   $this->view->render('reports/wallets_list');
}

function memberstransactionspdf($start, $end){
    
    $data = $this->model->getWalletTransactions(date_format(date_create($start), "Ymd"), date_format(date_create($end), "Ymd"));
    
	$pdf = new FPDF();
	$pdf->AddPage();
	$pdf->SetFont('Helvetica','b',16);
	$pdf->Cell(30,7,'Member Wallet Transactions from '. date_format(date_create($start), "d/m/Y") . ' to ' . date_format(date_create($end), "d/m/Y"),2);
    $pdf->Ln();
	$pdf->Ln();
	$pdf->SetFont('Helvetica','b',12);
	$pdf->Cell(35,6,'Date',1,0,'C');
	$pdf->Cell(30,6,'Type',1,0,'C');
	$pdf->Cell(15,6,'Memo',1,0,'C');
	$pdf->Cell(30,6,'From',1,0,'C');
	$pdf->Cell(30,6,'To',1,0,'C');
	$pdf->Cell(27,6,'Credit',1,0,'C');
	$pdf->Cell(27,6,'Debit',1,0,'C');
	
 	foreach ($data as $key => $value){
			$pdf->Ln();
			$pdf->SetFont('Helvetica','',10);
			$pdf->Cell(35,6, $value->date,1);
			$pdf->Cell(30,6, $value->type,1);
			$pdf->Cell(15,6, $value->memo,1);
			$pdf->Cell(30,6, $value->from,1);
			$pdf->Cell(30,6, $value->to,1);
			$pdf->Cell(27,6, ($value->status == "CR" ? $value->amount . " " . $value->asset_code : "") ,1);
			$pdf->Cell(27,6, ($value->status == "DR" ? $value->amount . " " . $value->asset_code : "") ,1);
			
	}
	
	$pdf->SetFont('Helvetica','b',15);
	$pdf->Ln();
	$pdf->Ln();
	//$pdf->Cell(30,6,'TOTAL WALLET BALANCES: '.number_format($total),3);   
	$pdf->Output();
}

function walletslistpdf(){
    try {
		$headers = getallheaders();
        $office = $headers['office'];
        $data = $this->model->getWallets($office);

        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Helvetica','b',16);
        $pdf->Cell(30,7,'Member List Report',2);
        $pdf->Ln();
        $pdf->Ln();
        $pdf->SetFont('Helvetica','b',12);
        $pdf->Cell(30,6,'Member No',1,0,'C');
        $pdf->Cell(35,6,'Member Name',1,0,'C');
        $pdf->Cell(35,6,'Wallet No',1,0,'C');
        $pdf->Cell(30,6,'Wallet Status',1,0,'C');
        $pdf->Cell(35,6,'Wallet Balance',1,0,'C');

        $total = 0;

        foreach ($data as $key => $value) {
            $total += $value["wallet_balance"];
            $pdf->Ln();
            $pdf->SetFont('Helvetica','',10);
            $pdf->Cell(30,6,$value["member_id"],1);
            $pdf->Cell(35,6,$value["firstname"]." ".$value["middlename"],1);
            $pdf->Cell(35,6,$value["wallet_account_number"],1);
            $pdf->Cell(30,6,$value["wallet_status"],1);
            $pdf->Cell(35,6,$value["wallet_balance"],1);
        }

        $pdf->SetFont('Helvetica','b',15);
        $pdf->Ln();
        $pdf->Ln();
        $pdf->Cell(30,6,'TOTAL WALLET BALANCES: '.number_format($total),3);

        header('Content-Type: application/pdf');
        $pdf->Output();
    } catch (Exception $e) {
        $errorResponse = array("status" => 500, "message" => $e->getMessage());
        header('Content-Type: application/json');
        http_response_code($errorResponse['status']);
        echo json_encode($errorResponse);
    }
}

function memberslistpdf(){
    try {
        $headers = getallheaders();
        $office = $headers['office'];

        $data = $this->model->membersList($office);

        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', 'b', 16);
        $pdf->Cell(30, 7, 'Member List Report', 2);
        $pdf->Ln();
        $pdf->Ln();
        $pdf->SetFont('Helvetica', 'b', 12);
        $pdf->Cell(30, 6, 'Member No', 1, 0, 'C');
        $pdf->Cell(35, 6, 'Member Name', 1, 0, 'C');
        $pdf->Cell(20, 6, 'Type', 1, 0, 'C');
        $pdf->Cell(30, 6, 'Tel No', 1, 0, 'C');
        $pdf->Cell(35, 6, 'Last updated on', 1, 0, 'C');
        $pdf->Cell(35, 6, 'Status', 1, 0, 'C');

        foreach ($data as $key => $value) {
            if ($value["legal_form"] == 'Personal') {
                $legal = "Personal";
            } else {
                $legal = "Institutional";
            }
            $pdf->Ln();
            $pdf->SetFont('Helvetica', '', 10);
            $pdf->Cell(30, 6, $value["c_id"], 1);
            $pdf->Cell(35, 6, $value["firstname"] . " " . $value["middlename"], 1);
            $pdf->Cell(20, 6, $legal, 1);
            $pdf->Cell(30, 6, $value["mobile_no"], 1);
            $pdf->Cell(35, 6, $value["last_updated_on"], 1);
            $pdf->Cell(35, 6, $value["status"], 1);
        }

        $pdf->Output();

    } catch (Exception $e) {
        $errorResponse = array("status" => 500, "message" => $e->getMessage());
        header('Content-Type: application/json');
        http_response_code($errorResponse['status']);
        echo json_encode($errorResponse);
    }
}

/* Loans Listing   */

function loans(){
    try {
        $headers = getallheaders();
        $office = $headers['office'];

        $data = $this->model->Getloanslist($office);

        header('Content-Type: application/json');
        echo json_encode($data);

    } catch (Exception $e) {
        $errorResponse = array("status" => 500, "message" => $e->getMessage());
        header('Content-Type: application/json');
        http_response_code($errorResponse['status']);
        echo json_encode($errorResponse);
    }
}

function loanspdf(){
    try {
        $headers = getallheaders();
        $office = $headers['office'];

        $data = $this->model->Getloanslist($office);

        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', 'b', 12);
        $pdf->Cell(30, 7, 'Loan List Report', 2);
        $pdf->Ln();
        $pdf->Ln();
        $pdf->SetFont('Helvetica', 'b', 9);
        $pdf->Cell(20, 6, 'Member No', 1, 0, 'C');
        $pdf->Cell(20, 6, 'Acc No', 1, 0, 'C');
        $pdf->Cell(25, 6, 'Acc Name', 1, 0, 'C');
        $pdf->Cell(25, 6, 'Acc Opened On', 1, 0, 'C');
        $pdf->Cell(25, 6, 'Proposed Amt', 1, 0, 'C');
        $pdf->Cell(30, 6, 'Approved Amt', 1, 0, 'C');
        $pdf->Cell(25, 6, 'Disbursed Amt', 1, 0, 'C');
        $pdf->Cell(20, 6, 'Loan Status', 1, 0, 'C');

        $total = 0;
        foreach ($data as $key => $value) {
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->Ln();
            $total = $total + $value["principal_disbursed"];
            if (!empty($value["firstname"])) {
                $name = $value["firstname"] . " " . $value["middlename"] . " " . $value["lastname"];
            } else {
                $name = $value["company_name"];
            }
            $pdf->Cell(20, 6, $value["c_id"], 1);
            $pdf->Cell(20, 6, $value["account_no"], 1);
            $pdf->Cell(25, 6, $name, 1);
            $pdf->Cell(25, 6, $value["submittedon_date"], 1);
            $pdf->Cell(25, 6, number_format($value["principal_amount_proposed"]), 1);
            $pdf->Cell(30, 6, number_format($value["approved_principal"]), 1);
            $pdf->Cell(25, 6, number_format($value["principal_disbursed"]), 1);
            $pdf->Cell(20, 6, $value["loan_status"], 1);
        }

        $pdf->SetFont('Helvetica', 'b', 15);
        $pdf->Ln();
        $pdf->Ln();
        $pdf->Cell(30, 6, 'TOTAL: ' . number_format($total), 3);

        $pdf->Output();

    } catch (Exception $e) {
        $errorResponse = array("status" => 500, "message" => $e->getMessage());
        header('Content-Type: application/json');
        http_response_code($errorResponse['status']);
        echo json_encode($errorResponse);
    }
}

function LoansPending(){
    try {
        $headers = getallheaders();
        $office = $headers['office'];

        $data = $this->model->getPendingLoans($office);

        header('Content-Type: application/json');
        echo json_encode($data);

    } catch (Exception $e) {
        $errorResponse = array("status" => 500, "message" => $e->getMessage());
        header('Content-Type: application/json');
        http_response_code($errorResponse['status']);
        echo json_encode($errorResponse);
    }
}

function loanspendingpdf(){
    try {
        $headers = getallheaders();
        $office = $headers['office'];

        $data = $this->model->getPendingLoans($office);

        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', 'b', 12);
        $pdf->Cell(30, 7, 'Loans Pending Approval Report', 2);
        $pdf->Ln();
        $pdf->Ln();
        $pdf->SetFont('Helvetica', 'b', 9);
        $pdf->Cell(30, 6, 'Member No', 1, 0, 'C');
        $pdf->Cell(35, 6, 'Member Name', 1, 0, 'C');
        $pdf->Cell(30, 6, 'Loan Acc No', 1, 0, 'C');
        $pdf->Cell(30, 6, 'Applied Amt', 1, 0, 'C');
        $pdf->Cell(30, 6, 'Loan Status', 1, 0, 'C');

        if (!empty($value["firstname"])) { 
            $name = $value["firstname"] . " " . $value["middlename"] . " " . $value["lastname"];
        } else { 
            $name = $value["company_name"];
        }

        if (count($data) > 0) {
            foreach ($data as $key => $value) {
                $pdf->SetFont('Helvetica', '', 8);
                $pdf->Ln();
                $pdf->Cell(30, 6, $value["c_id"], 1);
                $pdf->Cell(35, 6, $name, 1);
                $pdf->Cell(30, 6, $value["account_no"], 1);
                $pdf->Cell(30, 6, number_format($value["principal_amount_proposed"]), 1);
                $pdf->Cell(30, 6, $value["loan_status"], 1);
            }
        }

        $pdf->Output();

    } catch (Exception $e) {
        $errorResponse = array("status" => 500, "message" => $e->getMessage());
        header('Content-Type: application/json');
        http_response_code($errorResponse['status']);
        echo json_encode($errorResponse);
    }
}

function DisbursedLoans(){
    try {
        $headers = getallheaders();
        $office = $headers['office'];

        $data = $this->model->getDisbursedLoans($office);

        header('Content-Type: application/json');
        echo json_encode($data);

    } catch (Exception $e) {
        $errorResponse = array("status" => 500, "message" => $e->getMessage());
        header('Content-Type: application/json');
        http_response_code($errorResponse['status']);
        echo json_encode($errorResponse);
    }
}

function disbursedloanspdf(){
    try {
        $headers = getallheaders();
        $office = $headers['office'];

        $data = $this->model->getDisbursedLoans($office);

        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Helvetica','b',16);
        $pdf->Cell(30,7,'Disbursed Loans Report',2);
        $pdf->Ln();
        $pdf->Ln();
        $pdf->SetFont('Helvetica','b',12);
        $pdf->Cell(30,6,'Member No',1,0,'C');
        $pdf->Cell(35,6,'Name',1,0,'C');
        $pdf->Cell(35,6,'Loan Acc No.',1,0,'C');
        $pdf->Cell(45,6,'Principal Disbursed',1,0,'C');
        $pdf->Cell(30,6,'Loan Status',1,0,'C');

        foreach ($data as $key => $value){
            $pdf->Ln();
            $pdf->SetFont('Helvetica','',10);
            $pdf->Cell(30,6,$value["c_id"],1);
            $pdf->Cell(35,6,$value["firstname"]." ". $value["middlename"],1);
            $pdf->Cell(35,6,$value["account_no"],1);
            $pdf->Cell(45,6,number_format($value["principal_disbursed"]),1);
            $pdf->Cell(30,6,$value["loan_status"],1);
        }

        $pdf->Output();

    } catch (Exception $e) {
        $errorResponse = array("status" => 500, "message" => $e->getMessage());
        header('Content-Type: application/json');
        http_response_code($errorResponse['status']);
        echo json_encode($errorResponse);
    }
}

function ApprovedLoans(){
    try {
        $headers = getallheaders();
        $office = $headers['office'];

        $data = $this->model->getApprovedLoans($office);

        if (empty($data)) {
            $response = array("status" => 404, "message" => "No approved loans found.");
        } else {
            $response = array("status" => 200, "message" => "Approved loans retrieved successfully.", "data" => $data);
        }

        header('Content-Type: application/json');
        http_response_code($response['status']);
        echo json_encode($response);

    } catch (Exception $e) {
        $errorResponse = array("status" => 500, "message" => $e->getMessage());
        header('Content-Type: application/json');
        http_response_code($errorResponse['status']);
        echo json_encode($errorResponse);
    }
}

function approvedloanspdf() {
    try {
        $headers = getallheaders();
        $office = $headers['office'];

        $data = $this->model->getApprovedLoans($office);

        if (empty($data)) {
            throw new Exception("No approved loans found.");
        }

        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', 'b', 12);
        $pdf->Cell(30, 7, 'Loans Approved Report', 2);
        $pdf->Ln();
        $pdf->Ln();
        $pdf->SetFont('Helvetica', 'b', 9);
        $pdf->Cell(30, 6, 'Member No', 1, 0, 'C');
        $pdf->Cell(35, 6, 'Member Name', 1, 0, 'C');
        $pdf->Cell(30, 6, 'Loan Acc No', 1, 0, 'C');
        $pdf->Cell(30, 6, 'Approved Amt', 1, 0, 'C');
        $pdf->Cell(30, 6, 'Loan Status', 1, 0, 'C');

        foreach ($data as $key => $value) {
            if (!empty($value["firstname"])) {
                $name = $value["firstname"] . " " . $value["middlename"] . " " . $value["lastname"];
            } else {
                $name = $value["company_name"];
            }
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->Ln();
            $pdf->Cell(30, 6, $value["c_id"], 1);
            $pdf->Cell(35, 6, $name, 1);
            $pdf->Cell(30, 6, $value["account_no"], 1);
            $pdf->Cell(30, 6, number_format($value["principal_disbursed"]), 1);
            $pdf->Cell(30, 6, $value["loan_status"], 1);
        }

        $pdf->Output();

    } catch (Exception $e) {
        $errorResponse = array("status" => 500, "message" => $e->getMessage());
        header('Content-Type: application/json');
        http_response_code($errorResponse['status']);
        echo json_encode($errorResponse);
    }
}

function provisioning() {
    try {
        $headers = getallheaders();
        $office = $headers['office'];
        $data = $this->model->getLoanProvisioning($office);
        $response = array(
            "status" => 200,
            "message" => "Provisioning data retrieved successfully.",
            "data" => $data
        );

        header('Content-Type: application/json');
        http_response_code($response['status']);

        echo json_encode($response);

    } catch (Exception $e) {
        $errorResponse = array(
            "status" => 500,
            "message" => $e->getMessage()
        );
        header('Content-Type: application/json');
        http_response_code($errorResponse['status']);
        echo json_encode($errorResponse);
    }
}

function provisioningpdf() {
    try {
        $headers = getallheaders();
        $office = $headers['office'];

        $data = $this->model->getLoanProvisioning($office);

        if (empty($data)) {
            $pdf = new FPDF();
            $pdf->AddPage();
            $pdf->SetFont('Helvetica', 'b', 12);
            $pdf->Cell(0, 10, 'No data found for the selected office.', 0, 1);
            $pdf->Output();
        } else {
            $pdf = new FPDF();
            $pdf->AddPage();
            $pdf->SetFont('Helvetica', 'b', 12);
            $pdf->Cell(30, 7, 'Loan Provisioning Report', 2);
            $pdf->Ln();
            $pdf->Ln();
            $pdf->SetFont('Helvetica', 'b', 9);
            $pdf->Cell(30, 6, 'Description', 1, 0, 'C');
            $pdf->Cell(35, 6, 'Number of Accounts', 1, 0, 'C');
            $pdf->Cell(35, 6, 'Outstanding Balance', 1, 0, 'C');
            $pdf->Cell(20, 6, 'Rate', 1, 0, 'C');
            $pdf->Cell(30, 6, 'Amount', 1, 0, 'C');

            foreach ($data as $key => $value) {
                $pdf->SetFont('Helvetica', '', 8);
                $pdf->Ln();
                $pdf->Cell(30, 6, $value["c_id"], 1);
                $name = (!empty($value["firstname"])) ? $value["firstname"] . " " . $value["middlename"] . " " . $value["lastname"] : $value["company_name"];
                $pdf->Cell(35, 6, $name, 1);
                $pdf->Cell(35, 6, $value["account_no"], 1);
                $pdf->Cell(20, 6, number_format($value["principal_disbursed"]), 1);
                $pdf->Cell(30, 6, $value["loan_status"], 1);
            }

            $pdf->Output();
        }
    } catch (Exception $e) {
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', 'b', 12);
        $pdf->Cell(0, 10, 'An error occurred: ' . $e->getMessage(), 0, 1);
        $pdf->Output();
    }
}

function detailedprovisioning() {
    try {
        $headers = getallheaders();
        $office = $headers['office'];

        $data = $this->model->getProvisionDefinitions($office);

        if (empty($data)) {
            $response = array("status" => 404, "message" => "No provisioning definitions found.");
        } else {
            $response = array("status" => 200, "message" => "Provisioning definitions retrieved successfully.", "data" => $data);
        }

        header('Content-Type: application/json');
        http_response_code($response['status']);
        echo json_encode($response);

    } catch (Exception $e) {
        $errorResponse = array("status" => 500, "message" => $e->getMessage());
        header('Content-Type: application/json');
        http_response_code($errorResponse['status']);
        echo json_encode($errorResponse);
    }
}

function getdetailedprovisioning() {
    try {
        $jsonData = file_get_contents('php://input');
        $data = json_decode($jsonData, true);
        $id = $data['id'];

        $headers = getallheaders();
        $office = $headers['office'];

        $provisioningData = $this->model->getDetailedProvisioning($office, $id);

        if (empty($provisioningData)) {
            $response = array("status" => 404, "message" => "No detailed provisioning data found.");
        } else {
            $response = array("status" => 200, "message" => "Detailed provisioning data retrieved successfully.", "data" => $provisioningData);
        }

        header('Content-Type: application/json');
        http_response_code($response['status']);
        echo json_encode($response);

    } catch (Exception $e) {
        $errorResponse = array("status" => 500, "message" => $e->getMessage());
        header('Content-Type: application/json');
        http_response_code($errorResponse['status']);
        echo json_encode($errorResponse);
    }
}

/* Savings   */

function savingslist(){
    try {
        $headers = getallheaders();
        $office = $headers['office'];

        $savingsListData = $this->model->savingslist($office);

        $response = array("status" => 200, "data" => $savingsListData);

        header('Content-Type: application/json');
        echo json_encode($response);
    } catch (Exception $e) {
        $errorResponse = array("status" => 500, "message" => $e->getMessage());
        header('Content-Type: application/json');
        http_response_code($errorResponse['status']);
        echo json_encode($errorResponse);
    }
}

function savingslistpdf(){

	$data = $this->model->savingslist();
	$pdf = new FPDF();
	$pdf->AddPage();
	$pdf->SetFont('Helvetica','b',16);
	$pdf->Cell(30,7,'Savings List Report',2);
    $pdf->Ln();
	$pdf->Ln();
	$pdf->SetFont('Helvetica','b',9);
	$pdf->Cell(20,6,'Member No',1,0,'C');
	$pdf->Cell(20,6,'Account No',1,0,'C');
	$pdf->Cell(30,6,'Account Name',1,0,'C');
	$pdf->Cell(30,6,'Opened On',1,0,'C');
	$pdf->Cell(30,6,'Acc Balance',1,0,'C');
	$pdf->Cell(30,6,'Acc Status',1,0,'C');
	$pdf->Cell(30,6,'Last Transact Date',1,0,'C');
    $total=0;
	foreach ($data as $key => $value){
		$pdf->Ln();
		$pdf->SetFont('Helvetica','',6);
		$total=$total+$value["running_balance"];
		$pdf->Cell(20,6,$value["c_id"],1);
		$pdf->Cell(20,6,$value["account_no"],1);
		$pdf->Cell(30,6,$value["firstname"]." ". $value["middlename"]." ". $value["lastname"] ,1);
		$pdf->Cell(30,6,$value["submittedon_date"],1);
		$pdf->Cell(30,6,number_format($value["running_balance"]),1);
		$pdf->Cell(30,6,$value["account_status"],1);
		$pdf->Cell(30,6,$value["last_updated_on"],1);
		
	} 
	 
	$pdf->SetFont('Helvetica','b',15);
	$pdf->Ln();
	$pdf->Ln();
	$pdf->Cell(30,6,'TOTAL: '.number_format($total),3);             
	$pdf->Output();				
}

function savingsbystatus(){
    try {
        $headers = getallheaders();
        $office = $headers['office'];

        $savingsByStatusData = $this->model->getSavingsByStatus($office);

        $response = array("status" => 200, "data" => $savingsByStatusData);

        header('Content-Type: application/json');
        echo json_encode($response);
    } catch (Exception $e) {
        $errorResponse = array("status" => 500, "message" => $e->getMessage());
        header('Content-Type: application/json');
        http_response_code($errorResponse['status']);
        echo json_encode($errorResponse);
    }
}

function savingsbystatuspdf() {
    try {
        $headers = getallheaders();
        $office = $headers['office'];

        $data = $this->model->getSavingsByStatus($office);

        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', 'b', 16);
        $pdf->Cell(30, 7, 'Summary of Savings By Status', 2);
        $pdf->Ln();
        $pdf->Ln();
        $pdf->SetFont('Helvetica', 'b', 12);
        $pdf->Cell(50, 6, 'Account Status', 1, 0, 'C');
        $pdf->Cell(50, 6, 'Number Of Accounts', 1, 0, 'C');
        $pdf->Cell(50, 6, 'Balance Of Accounts', 1, 0, 'C');

        if (count($data) > 0) {
            $accounts = 0;
            $balance = 0;
            foreach ($data as $key => $value) {
                $pdf->Ln();
                $pdf->SetFont('Helvetica', '', 10);
                $accounts = $accounts + $value["no_of_accounts"];
                $balance = $balance + $value["balance_of_account"];
                $pdf->Cell(50, 6, $value["status"], 1);
                $pdf->Cell(50, 6, number_format($value["no_of_accounts"]), 1);
                $pdf->Cell(50, 6, number_format($value["balance_of_account"]), 1);
            }
            $pdf->Ln();
            $pdf->Cell(50, 6, "Totals", 1);
            $pdf->Cell(50, 6, number_format($accounts), 1);
            $pdf->Cell(50, 6, number_format($balance), 1);
        }

        $pdf->Output();
    } catch (Exception $e) {
        $errorResponse = array("status" => 500, "message" => $e->getMessage());
        header('Content-Type: application/json');
        http_response_code($errorResponse['status']);
        echo json_encode($errorResponse);
    }
}

function savingsbyproduct(){
    try {
        $headers = getallheaders();
        $office = $headers['office'];

        $savingsByProductData = $this->model->getSavingsByProduct($office);

        $response = array("status" => 200, "data" => $savingsByProductData);

        header('Content-Type: application/json');
        echo json_encode($response);
    } catch (Exception $e) {
        $errorResponse = array("status" => 500, "message" => $e->getMessage());
        header('Content-Type: application/json');
        http_response_code($errorResponse['status']);
        echo json_encode($errorResponse);
    }
}

function savingsbyproductpdf() {
    try {
        $headers = getallheaders();
        $office = $headers['office'];

        $data = $this->model->getSavingsByProduct($office);

        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', 'b', 16);
        $pdf->Cell(30, 7, 'Summary of Savings By Product', 2);
        $pdf->Ln();
        $pdf->Ln();
        $pdf->SetFont('Helvetica', 'b', 12);
        $pdf->Cell(50, 6, 'Product Type', 1, 0, 'C');
        $pdf->Cell(50, 6, 'Savings Account', 1, 0, 'C');
        $pdf->Cell(50, 6, 'Group savings', 1, 0, 'C');

        if (count($data) > 0) {
            $accounts = 0;
            $balance = 0;
            foreach ($data as $key => $value) {
                $pdf->Ln();
                $pdf->SetFont('Helvetica', '', 10);
                $accounts = $accounts + $value["no_of_accounts"];
                $balance = $balance + $value["balance_of_account"];
                $pdf->Cell(50, 6, $value["product_type"], 1);
                $pdf->Cell(50, 6, number_format($value["no_of_accounts"]), 1);
                $pdf->Cell(50, 6, number_format($value["balance_of_account"]), 1);
            }
            $pdf->Ln();
            $pdf->Cell(50, 6, "Totals", 1);
            $pdf->Cell(50, 6, number_format($accounts), 1);
            $pdf->Cell(50, 6, number_format($balance), 1);
        }

        $pdf->Output();
    } catch (Exception $e) {
        $errorResponse = array("status" => 500, "message" => $e->getMessage());
        header('Content-Type: application/json');
        http_response_code($errorResponse['status']);
        echo json_encode($errorResponse);
    }
}

function fixeddepositList(){
    try {
        $headers = getallheaders();
        $office = $headers['office'];

        $fixedDepositData = $this->model->fixeddepositList($office);

        $response = array("status" => 200, "data" => $fixedDepositData);

        header('Content-Type: application/json');
        echo json_encode($response);
    } catch (Exception $e) {
        $errorResponse = array("status" => 500, "message" => $e->getMessage());
        header('Content-Type: application/json');
        http_response_code($errorResponse['status']);
        echo json_encode($errorResponse);
    }
}
function exportfixeddepositpdf(){
    try {
        $headers = getallheaders();
        $office = $headers['office'];

        $reportdata = $this->model->fixeddepositList($office);
        
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Helvetica','b',16);
        $pdf->Cell(30,7,'Fixed Deposit List Report',2);
        $pdf->Ln();
        $pdf->Ln();
        $pdf->SetFont('Helvetica','',6);
        $pdf->Cell(20,6,'Member No',1);
        $pdf->Cell(20,6,'Account No',1);
        $pdf->Cell(20,6,'Account Name',1);
        $pdf->Cell(30,6,'Opened On',1);
        $pdf->Cell(20,6,'Amount Fixed',1);
        $pdf->Cell(15,6,'Rate',1);
        $pdf->Cell(15,6,'Maturity Date',1);
        $pdf->Cell(30,6,'Last Transaction Date',1);
        $pdf->Cell(15,6,'Status',1);
        
        foreach ($reportdata as $key => $value){
            $pdf->Ln();
            $pdf->Cell(20,6,$value["c_id"],1);
            $pdf->Cell(20,6,$value["account_no"],1);
            $pdf->Cell(20,6,$value["firstname"]." ". $value["middlename"]." ". $value["lastname"] ,1);
            $pdf->Cell(20,6,$value["submittedon_date"],1);
            $pdf->Cell(20,6,$value["amount_fixed"],1);
            $pdf->Cell(20,6,$value["interest_rate"],1);
            $pdf->Cell(20,6,$value["maturity_date"],1);
            $pdf->Cell(20,6,$value["last_updated_on"],1);
            $pdf->Cell(20,6,$value["account_status"],1);
        }

        $pdf->Output();
    } catch (Exception $e) {
        $errorResponse = array("status" => 500, "message" => $e->getMessage());
        header('Content-Type: application/json');
        http_response_code($errorResponse['status']);
        echo json_encode($errorResponse);
    }
}

function fixeddepositbystatus(){
    try {
        $headers = getallheaders();
        $office = $headers['office'];

        $fixedDepositData = $this->model->getFixedByStatus($office);

        $response = array("status" => 200, "data" => $fixedDepositData);

        header('Content-Type: application/json');
        echo json_encode($response);
    } catch (Exception $e) {
        $errorResponse = array("status" => 500, "message" => $e->getMessage());
        header('Content-Type: application/json');
        http_response_code($errorResponse['status']);
        echo json_encode($errorResponse);
    }
}

function fixeddepositbyproduct(){
    try {
        $headers = getallheaders();
        $office = $headers['office'];

        $fixedDepositData = $this->model->getFixedByProduct($office);

        $response = array("status" => 200, "data" => $fixedDepositData);

        header('Content-Type: application/json');
        echo json_encode($response);
    } catch (Exception $e) {
        $errorResponse = array("status" => 500, "message" => $e->getMessage());
        header('Content-Type: application/json');
        http_response_code($errorResponse['status']);
        echo json_encode($errorResponse);
    }
}

/* Shares Listing   */

function ShareholdersList() {
    try {
        $headers = getallheaders();
        $office = $headers['office'];

        $shareholdersData = $this->model->ShareHoldersLists($office);

        if (empty($shareholdersData)) {
            $response = array("status" => 404, "message" => "No shareholder data found.");
        } else {
            $response = array("status" => 200, "message" => "Shareholder data retrieved successfully.", "data" => $shareholdersData);
        }

        header('Content-Type: application/json');
        http_response_code($response['status']);
        echo json_encode($response);

    } catch (Exception $e) {
        $errorResponse = array("status" => 500, "message" => $e->getMessage());
        header('Content-Type: application/json');
        http_response_code($errorResponse['status']);
        echo json_encode($errorResponse);
    }
}

function shareholderslistpdf() {
    try {
        $headers = getallheaders();
        $office = $headers['office'];

        $shareholdersData = $this->model->ShareHoldersLists($office);

        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', 'b', 12);
        $pdf->Cell(30, 7, 'Share Holders List Report', 2);
        $pdf->Ln();
        $pdf->Ln();
        $pdf->SetFont('Helvetica', 'b', 9);
        $pdf->Cell(20, 6, 'Member No', 1, 0, 'C');
        $pdf->Cell(20, 6, 'Account No', 1, 0, 'C');
        $pdf->Cell(20, 6, 'Acc Name', 1, 0, 'C');
        $pdf->Cell(30, 6, 'Opened On', 1, 0, 'C');
        $pdf->Cell(20, 6, 'Shares', 1, 0, 'C');
        $pdf->Cell(30, 6, 'Balance on Acc', 1, 0, 'C');
        $pdf->Cell(25, 6, 'Status', 1, 0, 'C');
        $pdf->Cell(30, 6, 'Last Trans Date', 1, 0, 'C');

        $totalShares = 0;
        $totalBalance = 0;
        
        foreach ($shareholdersData as $key => $value) {
            $totalBalance += $value["amount"];
            $totalShares += $value["shares"];
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->Ln();
            $pdf->Cell(20, 6, $value["member"], 1);
            $pdf->Cell(20, 6, $value["account_no"], 1);
            $pdf->Cell(20, 6, $value["name"], 1);
            $pdf->Cell(30, 6, $value["opened"], 1);
            $pdf->Cell(20, 6, $value["shares"], 1);
            $pdf->Cell(30, 6, number_format($value["amount"]), 1);
            $pdf->Cell(25, 6, $value["status"], 1);
            $pdf->Cell(30, 6, $value["last_updated_on"], 1);
        }

        $pdf->SetFont('Helvetica', 'b', 15);
        $pdf->Ln();
        $pdf->Ln();
        $pdf->Cell(30, 6, 'TOTAL SHARES: ' . number_format($totalShares), 3);
        $pdf->Ln();
        $pdf->Cell(30, 6, 'TOTAL BALANCE ON ACCOUNT: ' . number_format($totalBalance), 3);

        $pdf->Output();
    } catch (Exception $e) {
        $errorResponse = array("status" => 500, "message" => $e->getMessage());
        header('Content-Type: application/json');
        http_response_code($errorResponse['status']);
        echo json_encode($errorResponse);
    }
}

// function shareAccountsbyStatus(){
// $this->view->shares = $this->model->getSharesByStatus();	
// $this->view->render('reports/shares/sharesbystatus');
// }
function shareAccountsbyStatus() {
    try {
        $shareData = $this->model->getSharesByStatus();

        if (empty($shareData)) {
            $response = array("status" => 404, "message" => "No share account data found.");
        } else {
            $response = array("status" => 200, "message" => "Share account data retrieved successfully.", "data" => $shareData);
        }

        header('Content-Type: application/json');
        http_response_code($response['status']);
        echo json_encode($response);

    } catch (Exception $e) {
        $errorResponse = array("status" => 500, "message" => $e->getMessage());
        header('Content-Type: application/json');
        http_response_code($errorResponse['status']);
        echo json_encode($errorResponse);
    }
}


function shareAccountsbyStatuspdf() {
    $data = $this->model->getSharesByStatus();

    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Share Accounts By Status Report', 0, 1, 'C');

    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->Cell(45, 8, 'Account Status', 1);
    $pdf->Cell(45, 8, 'Number of Accounts', 1);
    $pdf->Cell(45, 8, 'Balance of Accounts', 1);
    $pdf->Ln();

    $accountsTotal = 0;
    $balanceTotal = 0;

    foreach ($data as $key => $value) {
        $accountsTotal += $value["no_of_accounts"];
        $balanceTotal += $value["balance_of_account"];

        $pdf->SetFont('Helvetica', '', 8);
        $pdf->Cell(45, 8, $value["status"], 1);
        $pdf->Cell(45, 8, number_format($value["no_of_accounts"]), 1);
        $pdf->Cell(45, 8, number_format($value["balance_of_account"]), 1);
        $pdf->Ln();
    }

    $pdf->SetFont('Helvetica', 'I', 12);
    $pdf->Ln();
    $pdf->Cell(0, 10, 'Total Number of Accounts: ' . number_format($accountsTotal), 0, 1);
    $pdf->Cell(0, 10, 'Total Balance on Accounts: ' . number_format($balanceTotal), 0, 1);

    $pdf->Output();
}

function shareAccountsByProduct() {
    try {
        $headers = getallheaders();
        $office = $headers['office'];

        $sharesData = $this->model->getSharesByProduct($office);

        if (empty($sharesData)) {
            $response = array("status" => 404, "message" => "No share account data found.");
        } else {
            $response = array("status" => 200, "message" => "Share account data retrieved successfully.", "data" => $sharesData);
        }

        header('Content-Type: application/json');
        http_response_code($response['status']);
        echo json_encode($response);

    } catch (Exception $e) {
        $errorResponse = array("status" => 500, "message" => $e->getMessage());
        header('Content-Type: application/json');
        http_response_code($errorResponse['status']);
        echo json_encode($errorResponse);
    }
}



/* Staff Listing   */
function staff(){
	
$this->view->render('reports/clientList_report');
}
function allstaffList(){
$this->view->Members = $this->model->clientsList();
$this->view->render('reports/clientList_report');
}
function branchstaff($id){
$this->view->Members = $this->model->getBranchStaff();
$this->view->render('reports/clientList_report');
}


/* Accounting   */
function accounting(){

$this->view->render('reports/accounting_reports');
}
function cashFlowStatement(){
	if ($_SESSION['Isheadoffice'] == 'Yes') {
		$this->view->saccobranches = $this->model->getSaccoBranches();
	}
	$this->view->render('reports/cashflowstatementreport');
}
function runcashFlowStatement(){
	$data=$_POST;
	if (empty($data)) {
		header('Location: ' . URL."reports/cashFlowStatement"); 
	} else {
		$this->view->incomes=$this->model->getIncomeAccounts($data);
		$this->view->expenses=$this->model->getExpenseAccounts($data);
		$this->view->render('reports/cashflowstatement_output');
	}
}


function glcomparisonreport(){

	$count = $this->model->getGlAccountsCount();
	if($count < 5){

		header('Location: ' . URL . 'accounting/?msg=glreport'); 

	} else {

		$this->view->GL_wallet = $this->model->getWalletGL();	
		$this->view->C_wallet = $this->model->getWalletBalance();

		$this->view->GL_savings = $this->model->getSavingsGL();	
		$this->view->C_savings = $this->model->getSavingsBalance();


		$this->view->GL_loans = $this->model->getloansGL();	
		$this->view->C_loans = $this->model->getloansBalance();

		$this->view->GL_shares = $this->model->getsharesGL();	
		$this->view->C_shares = $this->model->getsharesBalance();

		$this->view->GL_timedeposits = $this->model->gettimedepositsGL();	
		$this->view->C_timedeposits = $this->model->gettimedepositsBalance();

		$this->view->schedule = $this->model->scheduledetailsdue();

		$this->view->render('reports/glcomparisonreport');

	}
}

function portifolioreport(){
	//no_customers
$this->view->no_customers_loans = $this->model->no_customers_loans();
$this->view->no_customers_savings = $this->model->no_customers_savings();	
$this->view->no_customers_timedeposits = $this->model->no_customers_timedeposits();	
$this->view->no_customers_shares = $this->model->no_customers_shares();	

//no_accounts
$this->view->no_accounts_loans = $this->model->no_accounts_loans();
$this->view->no_accounts_savings = $this->model->no_accounts_savings();	
$this->view->no_accounts_timedeposits = $this->model->no_accounts_timedeposits();	
$this->view->no_accounts_shares = $this->model->no_accounts_shares();	

//loans
$this->view->amount_approved = $this->model->amount_approved(); 
$this->view->amount_disbursed = $this->model->amount_disbursed(); 

//balance
$this->view->balance_loans = $this->model->balance_loans();
$this->view->balance_savings = $this->model->balance_savings();	
$this->view->balance_timedeposits = $this->model->balance_timedeposits();	
$this->view->balance_shares = $this->model->balance_shares();	

$this->view->render('reports/portifolioreport');
}


function balanceSheet(){
	if ($_SESSION['Isheadoffice'] == 'Yes') {
		$this->view->saccobranches = $this->model->getSaccoBranches();
	}
	$this->view->render('reports/balancesheetreport');
}

function runbalanceSheet() {
    try {
        $headers = getallheaders();
        $office_id = $headers['office'];
        $Isheadoffice = $headers['Isheadoffice'];
        $branchid = $headers['branchid'];

        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data)) {
            $response = array("status" => 400, "message" => "No data provided.");
        } else {
            $assets = $this->model->getAssets($office_id, $Isheadoffice, $branchid, $data);
            $liabilities = $this->model->getLiabilities($office_id, $Isheadoffice, $branchid, $data);
            $equityData = $this->model->getEquity($office_id, $Isheadoffice, $branchid, $data);
            $incomeAccounts = $this->model->getIncomeAccounts($office_id, $Isheadoffice, $branchid, $data);
            $expenses = $this->model->getExpenseAccounts($office_id, $Isheadoffice, $branchid, $data);

            $response_data = array(
                "assets" => $assets,
                "liabilities" => $liabilities,
                "equityData" => $equityData,
                "incomeAccounts" => $incomeAccounts,
                "expenses" => $expenses
            );

            if (empty($response_data)) {
                $response = array("status" => 404, "message" => "No assets data found.");
            } else {
                $response = array("status" => 200, "message" => "Assets data retrieved successfully.", "data" => $response_data);
            }
        }

        header('Content-Type: application/json');
        http_response_code($response['status']);
        echo json_encode($response);

    } catch (Exception $e) {
        $errorResponse = array("status" => 500, "message" => $e->getMessage());
        header('Content-Type: application/json');
        http_response_code($errorResponse['status']);
        echo json_encode($errorResponse);
    }
}

// function incomeStatement(){
// 	if ($_SESSION['Isheadoffice'] == 'Yes') {
// 		$this->view->saccobranches = $this->model->getSaccoBranches();
// 	}
// 	$this->view->render('reports/incomestatementreport');
// }
function incomeStatement() {
    try {
        // Get the 'office' and 'Isheadoffice' values from headers
        $headers = getallheaders();
        $office = $headers['office']; // Assuming the header key is 'office'
        $isHeadOffice = $headers['Isheadoffice']; // Assuming the header key is 'Isheadoffice'

        // Check if the user is from the head office
        if ($isHeadOffice == 'Yes') {
            $this->view->saccobranches = $this->model->getSaccoBranches($office);
        }

        $this->view->render('reports/incomestatementreport');

    } catch (Exception $e) {
        // Handle any exceptions (e.g., invalid headers or data) and return an error response
        $errorResponse = array("status" => 500, "message" => $e->getMessage());
        header('Content-Type: application/json');
        http_response_code($errorResponse['status']);
        echo json_encode($errorResponse);
    }
}

function runIncomeStatement() {
    try {
        $headers = getallheaders();
        $office_id = $headers['office'];
        $Isheadoffice = $headers['Isheadoffice'];
        $branchid = $headers['branchid'];

        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data)) {
            $response = array("status" => 400, "message" => "No data provided.");
        } else {
            $incomeAccounts = $this->model->getIncomeAccounts($office_id, $Isheadoffice, $branchid, $data);
            $expenses = $this->model->getExpenseAccounts($office_id, $Isheadoffice, $branchid, $data);

            $response_data = array(
                "incomeAccounts" => $incomeAccounts,
                "expenses" => $expenses
            );

            if (empty($response_data)) {
                $response = array("status" => 404, "message" => "No assets data found.");
            } else {
                $response = array("status" => 200, "message" => "Assets data retrieved successfully.", "data" => $response_data);
            }
        }

        header('Content-Type: application/json');
        http_response_code($response['status']);
        echo json_encode($response);

    } catch (Exception $e) {
        $errorResponse = array("status" => 500, "message" => $e->getMessage());
        header('Content-Type: application/json');
        http_response_code($errorResponse['status']);
        echo json_encode($errorResponse);
    }
}

/* GL TRANSACTIONS   */
function gltransactionslists(){
	
$this->view->render('reports/gltransactions/gl_transaction_reports');
}

function GeneralLedgerReport() {
    try {
        $headers = getallheaders();
        $isHeadOffice = $headers['Isheadoffice'];

        $saccoBranches = array();
        if ($isHeadOffice == 'Yes') {
            $saccoBranches = $this->model->getSaccoBranches();
        }

        $glAccounts = $this->model->getGlaccounts();

        $responseData = array(
            "Isheadoffice" => $isHeadOffice,
            "saccobranches" => $saccoBranches,
            "glaccount" => $glAccounts
        );

        $response = array("status" => 200, "message" => "General ledger report data retrieved successfully.", "data" => $responseData);

        header('Content-Type: application/json');
        http_response_code($response['status']);
        echo json_encode($response);

    } catch (Exception $e) {
        $errorResponse = array("status" => 500, "message" => $e->getMessage());
        header('Content-Type: application/json');
        http_response_code($errorResponse['status']);
        echo json_encode($errorResponse);
    }
}

// function runLedgerReport(){
// 	$data=$_POST;
// 	if (empty($data)) {
// 		header('Location: ' . URL."reports/GeneralLedgerReport"); 
// 	} else {
// 		$this->view->ledger=$this->model->getGLreport($data);
// 		$this->view->account=$this->model->getGlaccountName($data['glaccount']);
// 		$this->view->balance_forward=$this->model->getBalance_Forward($data['startdon'],$data['glaccount']);
// 		//print_r($this->view->balance_forward);die();
// 		//$this->view->office=$this->model->getbranches($data);
// 		$this->view->render('reports/gltransactions/ledgerReport_output');
// 	}
// }
function runLedgerReport() {
    try {
        $headers = getallheaders();
        $office = $headers['office'];
        $isHeadOffice = $headers['Isheadoffice'];
        $branchid = $headers['branchid'];

        $jsonInput = file_get_contents('php://input');
        $data = json_decode($jsonInput, true);

        if (empty($data)) {
            $response = array("status" => 400, "message" => "Empty request data.");
        } else {
            $ledgerData = $this->model->getGLreport($office, $isHeadOffice, $data, $branchid);
            $accountName = $this->model->getGlaccountName($data);
            $balanceForward = $this->model->getBalance_Forward($office, $isHeadOffice, $data, $branchid);

            if (empty($ledgerData) || empty($accountName) || empty($balanceForward) || empty($officeData)) {
                $response = array("status" => 404, "message" => $balanceForward);
            } else {
                $response = array(
                    "status" => 200,
                    "message" => "Ledger report data retrieved successfully.",
                    "ledger" => $ledgerData,
                    "account" => $accountName,
                    "balance_forward" => $balanceForward,
                );
            }
        }

        header('Content-Type: application/json');
        http_response_code($response['status']);
        echo json_encode($response);

    } catch (Exception $e) {
        $errorResponse = array("status" => 500, "message" => $e->getMessage());
        header('Content-Type: application/json');
        http_response_code($errorResponse['status']);
        echo json_encode($errorResponse);
    }
}


function generalLedgerbybatch(){

$this->view->glaccount=$this->model->getGlaccounts();
$this->view->render('reports/gltransactions/generalLedgerbybatch');
}



function generalLedgerbypostdate(){

$this->view->glaccount=$this->model->getGlaccounts();
$this->view->render('reports/gltransactions/generalLedgerbypostdate');
}


function runLedgerReportbypostdate(){
$data=$_POST;
$this->view->ledger=$this->model->getGLreportByPostDate($data);
$this->view->render('reports/gltransactions/ledgerReportbydate_output');
}

/* TRIAL BALANCE   */
function gltrialbalance(){
	
$this->view->render('reports/trialbalance/trialbalance_reports');
}


function runfullTrialBalance(){
	$branches = $this->model->getSaccoBranches();
	if ($_SESSION['Isheadoffice'] == 'Yes' && !empty($branches)) {
		$this->view->saccobranches = $this->model->getSaccoBranches();
		$this->view->render('reports/trialbalance/trialbalancereport');
	} else {
	
		$this->view->sideA=$this->model->getGlaccountsA();
		$this->view->sideATotal=$this->model->getSideAAccountTotal();

		$this->view->sideB=$this->model->getGlaccountsB();
		$this->view->sideBTotal=$this->model->getSideBAccountTotal();
		$this->view->render('reports/trialbalance/fulltrialbalance_output');
	}
}

function runBranchTrialBalance(){
	$data=$_POST;
	if (empty($data)) {
		header('Location: ' . URL."reports/runfullTrialBalance"); 
	} else {
		$this->view->sideA=$this->model->getGlaccountsA($data);
		$this->view->sideATotal=$this->model->getSideAAccountTotal($data);
		
		$this->view->sideB=$this->model->getGlaccountsB($data);
		$this->view->sideBTotal=$this->model->getSideBAccountTotal($data);
		$this->view->render('reports/trialbalance/fulltrialbalance_output');
	}
}

function trialBalancebyperiod(){
$this->view->render('reports/trialbalance/trialbalancereport');
}

function runTrialBalance(){
	$data=$_POST;
	$this->view->sideA=$this->model->getGlaccountsA($data);
	$this->view->sideATotal=$this->model->getSideAAccountTotal($data);

	$this->view->sideB=$this->model->getGlaccountsB($data);
	$this->view->sideBTotal=$this->model->getSideBAccountTotal($data);
	$this->view->render('reports/trialbalance/trialbalance_output');
}


function runTrialbyGlheaders(){

$this->view->sideA=$this->model->getGlheadersA();
$this->view->sideATotal=$this->model->getSideAAccountTotal();
$this->view->sideB=$this->model->getGlheadersB();
$this->view->sideBTotal=$this->model->getSideBAccountTotal();
$this->view->render('reports/trialbalance/fulltrialbalance_output');
}
//trial balance by GL HEADERS BY PERIOD
function trialBalancebyheadersbyperiod(){
$this->view->render('reports/trialbalance/trialbalancebyglheadersbymonth');
}

function runTrialbyGlheadersByPeriod(){
$data=$_POST;
$this->view->sideA=$this->model->getGlheadersA($data);
$this->view->sideATotal=$this->model->getSideAAccountTotal($data);

$this->view->sideB=$this->model->getGlheadersB($data);
$this->view->sideBTotal=$this->model->getSideBAccountTotal($data);
$this->view->render('reports/trialbalance/fulltrialbalance_output');
}


//////////////////////////////////////////////////  STEVEN  //////////////////////////////////////////////////

function cgap(){

	$this->view->details = $this->model->getSaccoCGAPDetails();
	$this->view->unPaid = $this->model->getOutStandingPrincipalTotal();
	$this->view->totLoanBal = $this->model->getSaccoLoanBalanceTotal();
	$this->view->unPaid_overdue = $this->model->getUnPaidBalanceOverDueTotal();
	$this->view->loan_loss_reserve = $this->model->getTotalAccountBalance('LLR');
	$this->view->TotalOperatingExpenses = $this->model->getTotalOperatingExpenses();
	$this->view->AmountDisbursedDuringPeriod = $this->model->getAnnualTotalDisbursed();
	$this->view->TotalNumberOfDisbursements =  $this->model->getTotalNumberOfDisbursements();
	$this->view->NumberOfActiveBorrowers = $this->model->getTotalNumberOfActiveBorrowers();
	$this->view->NumberOfFieldAgents = $this->model->getTotalNumberOfFieldAgents();
	$this->view->SalariesAndBenefits = $this->model->getTotalAccountBalance('SB');
	$this->view->AverageLoansOutstanding = $this->model->getAverageLoansOutstanding();
	$this->view->TotalFinancialIncome = $this->model->getTotalFinancialIncome();
	$this->view->TotalCurrentAssets = $this->model->getTotalCurrentAssets();
	$this->view->PerformingAssets = $this->model->getTotalAccountBalance('PA');
	$this->view->FinancialCost = $this->model->getTotalAccountBalance('FC');
	$this->view->OperatingCost = $this->model->getTotalAccountBalance('OC');
	$this->view->LoanLossProv = $this->model->getTotalAccountBalance('LLP');


	$colors = array();
	//Portfolio In Arrears
	$this->view->PortfolioInArrears = number_format(($this->view->unPaid/$this->view->totLoanBal)*100, 2);
	$this->view->PortfolioInArrears_Color = $this->model->getColorCode($this->view->PortfolioInArrears, $this->view->details, 0);
	array_push($colors, $this->view->PortfolioInArrears_Color);

	//Loans With OverDue
	$this->view->LoansWithOverDue = number_format(($this->view->unPaid_overdue/$this->view->totLoanBal)*100, 2);
	$this->view->LoansWithOverDue_Color = $this->model->getColorCode($this->view->LoansWithOverDue, $this->view->details, 1);
	array_push($colors, $this->view->LoansWithOverDue_Color);

	//Loan Loss Reserve Ratio
	$this->view->LoanLossReserveRatio = number_format(($this->view->loan_loss_reserve/$this->view->totLoanBal)*100, 2);
	$this->view->LoanLossReserveRatio_Color = $this->model->getColorCode($this->view->LoanLossReserveRatio, $this->view->details, 2);
	array_push($colors, $this->view->LoanLossReserveRatio_Color);

	//Operating Efficiency Ratios
	$this->view->OperatingEfficiencyRatios = number_format(($this->view->TotalOperatingExpenses/$this->view->AmountDisbursedDuringPeriod)*100, 2);
	$this->view->OperatingEfficiencyRatios_Color = $this->model->getColorCode($this->view->OperatingEfficiencyRatios, $this->view->details, 3);
	array_push($colors, $this->view->OperatingEfficiencyRatios_Color);

	//Cost Per Loan Made
	$this->view->CostPerLoanMade = number_format(($this->view->TotalOperatingExpenses/$this->view->TotalNumberOfDisbursements)*100, 2);
	$this->view->CostPerLoanMade_Color = $this->model->getColorCode($this->view->CostPerLoanMade, $this->view->details, 4);
	array_push($colors, $this->view->CostPerLoanMade_Color);

	//Field Staff Efficiency
	$this->view->FieldStaffEfficiency = number_format(($this->view->NumberOfActiveBorrowers/$this->view->NumberOfFieldAgents)*100, 2);
	$this->view->FieldStaffEfficiency_Color = $this->model->getColorCode($this->view->FieldStaffEfficiency, $this->view->details, 5);
	array_push($colors, $this->view->FieldStaffEfficiency_Color);

	//Salaries To Loans Outstanding
	$this->view->SalariesToLoansOutstanding = number_format(($this->view->SalariesAndBenefits/$this->view->AverageLoansOutstanding)*100, 2);
	$this->view->SalariesToLoansOutstanding_Color = $this->model->getColorCode($this->view->SalariesToLoansOutstanding, $this->view->details, 6);
	array_push($colors, $this->view->SalariesToLoansOutstanding_Color);

	//Portfolio per Credit Officer
	$this->view->PortfolioPerCreditOfficer = number_format(($this->view->AverageLoansOutstanding/$this->view->NumberOfFieldAgents)*100, 2);
	$this->view->PortfolioPerCreditOfficer_Color = $this->model->getColorCode($this->view->PortfolioPerCreditOfficer, $this->view->details, 7);
	array_push($colors, $this->view->PortfolioPerCreditOfficer_Color);

	//Return on Performing Assets
	$this->view->ReturnOnPerformingAssets = number_format(($this->view->TotalFinancialIncome/$this->view->TotalCurrentAssets)*100, 2);
	$this->view->ReturnOnPerformingAssets_Color = $this->model->getColorCode($this->view->ReturnOnPerformingAssets, $this->view->details, 8);
	array_push($colors, $this->view->ReturnOnPerformingAssets_Color);

	//Operating Cost Ratio
	$this->view->OperatingCostRatio = number_format(($this->view->TotalOperatingExpenses/$this->view->PerformingAssets)*100, 2);
	$this->view->OperatingCostRatio_Color = $this->model->getColorCode($this->view->OperatingCostRatio, $this->view->details, 9);
	array_push($colors, $this->view->OperatingCostRatio_Color);

	//Operating Self Sufficiency
	$this->view->OperatingSelfSufficiency = number_format(($this->view->TotalFinancialIncome/($this->view->FinancialCost+$this->view->OperatingCost+$this->view->LoanLossProv))*100, 2);
	$this->view->OperatingSelfSufficiency_Color = $this->model->getColorCode($this->view->OperatingSelfSufficiency, $this->view->details, 10);
	array_push($colors, $this->view->OperatingSelfSufficiency_Color);

	//overallSaccoIndicator
	$saccoColorCode = $this->model->getOverallSaccoColorCode($colors);
	$this->view->saccoColorCode = $saccoColorCode;

	$this->view->render('reports/cgapreport');

}
}
