<?php
//ini_set('display_errors', '0');     # don't show any errors...
//error_reporting(E_ALL | E_STRICT);  # ...but do log them
class Loans_Model extends Model
{
    public function __construct()
    {
        parent::__construct();
        @session_start();
        $this->logUserActivity(null);
        if (!$this->checkTransactionStatus()) {
            header('Location: ' . URL);
        }
        $this->loans_calculations = new LoanCalculations();
        $this->tr = new TransactionSubmitter();
        //Auth::handleSignin();
    }

    /////////////////////////////////////////////steve changes///////////////////////////////////////////////

    function getGroupTotalLoanAmount($grp)
    {
        $details = $this->db->SelectData("SELECT * FROM m_group_client WHERE group_id = '$grp' AND loan_status = 'Closed' AND status='Active'");

        $total = 0;
        foreach ($details as $key => $value) {
            $total += $value['loan_amount'];
        }
        return $total;
    }
    ///changes steven
    function getBanks()
    {
        return $this->db->SelectData("SELECT * FROM m_bank where status='Active' ORDER BY name ASC");
    }

    ///changes steven
    function getBanksDetails($id)
    {
        return $this->db->SelectData("SELECT * FROM m_bank where status='Active' AND id='" . $id . "'");
    }

    ///changes steven
    function getMemberRefDetails($id)
    {
        return $this->db->SelectData("SELECT * FROM members where c_id='" . $id . "'");
    }

    ///changes steven
    function getCollateralDetails($id)
    {
        return $this->db->SelectData("SELECT * FROM m_loan_collateral where account_no='" . $id . "'");
    }

    ///steven changes
    function getloandetails($acc, $grp = null)
    {
        if (is_null($grp)) {
            return $this->db->SelectData("SELECT * FROM m_loan JOIN members ON m_loan.member_id=members.c_id WHERE account_no='" . $acc . "'");
        } else {
            return $this->db->SelectData("SELECT * FROM m_loan JOIN m_group ON m_loan.group_id=m_group.id WHERE m_loan.account_no='" . $acc . "'");
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////
    
    function loansList($office) {
        try {
            $results = $this->db->SelectData("SELECT * FROM m_loan JOIN members ON m_loan.member_id = members.c_id WHERE members.office_id = '$office' order by loan_id DESC");
    
            $grp_results = $this->db->SelectData("SELECT * FROM m_loan JOIN m_group ON m_loan.group_id = m_group.id WHERE m_group.office_id = '$office' order by loan_id DESC");
    
            $new_results = array_merge($results, $grp_results);
    
            if (empty($new_results)) {
                return '';
            } else {
                return $new_results;
            }
        } catch (Exception $e) {
            throw $e;
        }
    }    

    function pendingLoans()
    {
        $office = $_SESSION['office'];
        $results = $this->db->SelectData("SELECT * FROM m_loan JOIN members ON m_loan.member_id=members.c_id where loan_status='Pending' and members.office_id='" . $office . "' order by loan_id DESC");

        $grp_results = $this->db->SelectData("SELECT * FROM m_loan JOIN m_group ON m_loan.group_id = m_group.id where loan_status='Pending' and m_group.office_id='" . $office . "' order by loan_id DESC");

        $new_results = array_merge($results, $grp_results);

        if (empty($new_results)) {
            return '';
        } else {
            return $new_results;
        }
    }

    function loansToDisibus()
    {
        $office = $_SESSION['office'];
        $results = $this->db->SelectData("SELECT * FROM m_loan JOIN members ON m_loan.member_id=members.c_id and members.office_id='" . $office . "' where loan_status='Approved' order by loan_id DESC");

        $grp_results = $this->db->SelectData("SELECT * FROM m_loan JOIN m_group ON m_loan.group_id = m_group.id  and m_group.office_id='" . $office . "' where loan_status='Approved' order by loan_id DESC");

        $new_results = array_merge($results, $grp_results);

        if (empty($new_results)) {
            return '';
        } else {
            return $new_results;
        }
    }
    function getEmployees()
    {
        $office = $_SESSION['office'];
        return $this->db->SelectData("SELECT * FROM m_staff where office_id='" . $office . "'");
    }
    function getstaffList($id)
    {
        $office = $_SESSION['office'];

        return $this->db->SelectData("SELECT * FROM m_staff where office_id='" . $office . "' AND id='" . $id . "' ");
    }
    function getAccountNo($cid)
    {
        $result = $this->db->selectData("SELECT min(account_no) as account FROM m_savings_account WHERE member_id='" . $cid . "' ");

        return $result[0]['account'];
    }
    function currency()
    {
        return $this->db->SelectData("SELECT * FROM m_currency ");
    }
    function getMemberName($id)
    {
        $result = $this->db->selectData("SELECT * FROM members WHERE c_id='" . $id . "' ");
        if (!empty($result)) {
            if (!empty($result[0]['company_name'])) {
                $name = $result[0]['company_name'];
            } else {
                $name = $result[0]['firstname'] . " " . $result[0]['middlename'] . " " . $result[0]['lastname'];
            }
            return $name;
        } else {
            return '';
        }
    }

    function getloanProducts($id, $office)
    {
        if (!empty($id)) {
            return $this->db->SelectData("SELECT * FROM m_product_loan where office_id = '" . $office . "' AND  id='" . $id . "' AND status='open'");
        } else {
            return $this->db->SelectData("SELECT * FROM m_product_loan where office_id = '" . $office . "' and status='open'");
        }
    }

    function loansProductappleid($id)
    {
        $product_id = 0;

        $client_pdt = $this->db->SelectData("SELECT DISTINCT(product_id) AS product_id FROM  m_loan where member_id='" . $id . "' ");

        $no = count($client_pdt);

        $add = null;
        if ($no > 0) {
            for ($i = 0; $i < $no; $i++) {
                $add .= " AND id!='" . $client_pdt[$i]["product_id"] . "'";
            }
            $pdts = $this->db->SelectData("SELECT * FROM m_product_loan where status='open' $add");
        } else {
            $pdts = $this->db->SelectData("SELECT * FROM m_product_loan where status='open'");
        }
        return $pdts;
    }

    function editeloansProductappleid($account_no, $product_id)
    {
        $loandts = $this->db->SelectData("SELECT * FROM m_loan where account_no='" . $account_no . "'");

        $loan_product_id = $loandts[0]['product_id'];
        $member_id = $loandts[0]['member_id'];
        $product_id = 0;

        $client_pdt = $this->db->SelectData("SELECT DISTINCT(product_id) AS product_id FROM  m_loan where member_id='" . $member_id . "' AND product_id!='" . $loan_product_id . "' ");

        $no = count($client_pdt);

        $add = null;
        if ($no > 0) {
            for ($i = 0; $i < $no; $i++) {
                $add .= " AND id!='" . $client_pdt[$i]["product_id"] . "'";
            }
            $pdts = $this->db->SelectData("SELECT * FROM m_product_loan where status='open' $add");
            print_r(json_encode($pdts));
            die();
        } else {
            $pdts = $this->db->SelectData("SELECT * FROM m_product_loan where status='open'");
            print_r(json_encode($pdts));
            die();
        }

        return $option;
    }

    function getloanProduct()
    {
        $id = $_POST['product_id'];
        return $this->db->SelectData("SELECT * FROM m_product_loan where id='" . $id . "'");
    }
    function GetClientLoanProduct($cid, $id)
    {
        return $this->db->SelectData("SELECT * FROM m_loan where member_id = '" . $cid . "' AND product_id='" . $id . "' AND loan_status!='closed'");
    }

    function getloanProductcharge($id)
    {
        return $this->db->SelectData(
            "SELECT * FROM m_charge c INNER JOIN m_product_loan_charge pc

			where c.id=pc.charge_id and pc.product_loan_id='" .
                $id .
                "' order by c.id DESC"
        );
    }

    // function applyLoan($data){
		
    //     try {
    //         if (empty($data['cid']) || empty($data['product_id']) || empty($data['loanofficer']) || empty($data['principal']) || empty($data['guarantor1'])) {
    //             return $this->MakeJsonResponse(206, "fill all required fields", "");
    //         }

    //         $rs = $this->GetClientDetails($data['cid']);
    //         if (sizeof($rs) == 0) {
    //             return $this->MakeJsonResponse(404, "client not found", "");
    //         }

    //         $loan_acc = $this->LoanAccount();
    //         $loan_product = $this->getloanProducts($data['product_id']);
    //         $loan_productid = $loan_product[0]['id'];
    //         $rsInfo = $this->GetClientLoanProduct($data['cid'], $loan_productid);
    //         if (sizeof($rsInfo) > 0) {
    //             return $this->MakeJsonResponse(200, "member has pending loan product", "");
    //         }
    //         if (sizeof($loan_product) == 0) {
    //             return $this->MakeJsonResponse(204, "loan product not found", "");
    //         }

    //         $principal = str_replace(',', '', $data['principal']);

    //         $min_principal_amount = $loan_product[0]['min_principal_amount'];
    //         $max_principal_amount = $loan_product[0]['max_principal_amount'];

    //         if ($principal < $min_principal_amount || $principal > $max_principal_amount) {
    //             return $this->MakeJsonResponse(205, "loan out of range", "");
    //         }
			
	// 		$this->db->beginTransaction();

    //         if (isset($data['group_id'])) {
    //             $group_id = $data['group_id'];
    //         } else {
    //             $group_id = null;
    //         }

    //         $submit_date = date('Y-m-d');

    //         $s_date = date('Y-m-d');
    //         $created_by = $_SESSION['user_id'];

    //         $application_fee = $data['application_fee'];
    //         $disbursement_date = date('Y-m-d', strtotime($data['disbursement_date']));
	// 		$rate = str_replace(',', '', $loan_product[0]['nominal_interest_rate_per_period']);
			
	// 		$annualRate = $this->val->getAnnualInterest($loan_product[0]['interest_period'], $rate, $days_in_year=null);
			
			
			
    //         $postData = [
    //             'sacco_id' => $_SESSION['office'],
    //             'product_id' => $data['product_id'],
    //             'account_no' => $loan_acc,
    //             'member_id' => $data['cid'],
    //             'group_id' => $group_id,
    //             'submittedon_date' => $submit_date,
    //             'created_by' => $created_by,
    //             'submittedon_userid' => $_SESSION['user_id'],
    //             'loan_officer_id' => $data['loanofficer'],
    //             'principal_amount_proposed' => $principal,
    //             'number_of_installments' => str_replace(',', '', $data['duration']),
    //             'disbursedon_date' => $disbursement_date,
    //             'nominal_interest_rate_per_period' => $rate,
    //             'annual_nominal_interest_rate' => $annualRate,
    //             'loanpurpose' => $loan_product[0]['description'],
    //             'interest_period' => $loan_product[0]['interest_period'],
    //             'product_name' => $loan_product[0]['name'],
    //             'days_in_year' => $loan_product[0]['days_in_year'],
    //             'installment_option' => $loan_product[0]['installment_option'],
    //             'interest_method' => $loan_product[0]['interest_method'],
    //             'duration' => $loan_product[0]['duration'],
    //             'duration_value' => $data['duration'],
    //             'grace_period' => $loan_product[0]['grace_period'],
    //             'grace_period_value' => $loan_product[0]['grace_period_value'],
    //             'insurance' => $loan_product[0]['insurance'],
    //             'stamp_duty' => $loan_product[0]['stamp_duty'],
    //             'guarantor_1' => $data['guarantor1'],
    //             'guarantor_1_Amount' => $data['guarantor1_amount'],
    //             'guarantor_2' => $data['guarantor2'],
    //             'guarantor_2_Amount' => $data['guarantor2_amount'],
    //             's_consent' => $data['s_consent'],
    //             'witness' => $data['witnsess'],
    //             'loan_purpose' => $data['loan_purpose'],
    //             'frequency' => $loan_product[0]['interest_period'],
    //         ];

    //         $prodType = 2;
    //         $transactionType = 'Loan Application';
    //         $tran_id = $this->getTransactionID($transactionType);
    //         $transaction_charges = $this->getTransactionCharges($tran_id);
    //         $exemptions = $this->getMemberChargeExemptions($data['cid']);
    //         $total_charge_amount = 0;

    //         if (!empty($transaction_charges)) {
    //             foreach ($transaction_charges as $key => $value) {
    //                 if (is_null($exemptions) || !in_array($value, $exemptions)) {
    //                     $mapping_charges = $this->GetGLChargePointers($value['id'], $prodType, $transactionType, $tran_id);

    //                     if (!empty($mapping_charges)) {
    //                         $debt_id = $mapping_charges[0]["debit_account"];
    //                         $credit_id = $mapping_charges[0]["credit_account"];

    //                         $sideA = $this->getAccountSide($debt_id);
    //                         $sideB = $this->getAccountSide($credit_id);

    //                         $description = ucfirst($value['name']) . " Charge";

    //                         $uniq_id = $_SESSION['user_id'] . uniqid();
    //                         $transaction_id = "L" . $uniq_id;

    //                         $amount = $value['amount'];

    //                         $this->makeJournalEntry($debt_id, $_SESSION['office'], $_SESSION['user_id'], $uniq_id, $transaction_id, $amount, 'DR', $sideA, $description); //DR
    //                         $this->makeJournalEntry($credit_id, $_SESSION['office'], $_SESSION['user_id'], $uniq_id, $transaction_id, $amount, 'CR', $sideB, $description); //CR

    //                         $postCharges = [
    //                             'account_no' => $loan_acc,
    //                             'charge_id' => $value['id'],
    //                             'charge_name' => $value['name'],
    //                             'amount' => $value['amount'],
    //                         ];

    //                         $this->db->InsertData('m_loan_charge', $postCharges);

    //                         $postTransCharges = [
    //                             'transaction_id' => $transaction_id,
    //                             'charge_id' => $value['id'],
    //                             'trans_amount' => $amount,
    //                             'date' => date("Y-m-d H:i:s"),
    //                         ];

    //                         $this->db->InsertData('m_charge_transaction', $postTransCharges);
    //                     }
    //                 }
    //             }
    //         }

    //         $loan_id = $this->db->InsertData('m_loan', $postData);

    //         if (isset($data['collateral'])) {
    //             $num_collateral = count($data['collateral']);
    //             $num_collaterals = $data['collateral'];

    //             if ($num_collateral > 0) {
    //                 for ($i = 0; $i < $num_collateral; $i++) {
    //                     if (!empty($num_collaterals[$i])) {
    //                         $collateral = [
    //                             'account_no' => $loan_acc,
    //                             'name' => $num_collaterals[$i],
    //                         ];
    //                         $this->db->InsertData('m_loan_collateral', $collateral);
    //                     }
    //                 }
    //             }
    //         }
	// 		$this->db->commit();

    //         if (empty($data['group_id']) || is_null($data['group_id']) || $data['group_id'] == "") {
    //             return $this->MakeJsonResponse(100, "success " . $loan_acc, URL . "loans/loanAccountEquiry/" . $loan_acc);
    //         } else {
    //             return $this->MakeJsonResponse(100, "success", URL . 'groups/viewgroup/' . $data["group_id"] . '?actno=' . $loan_acc);
    //         }
    //     } catch (Exception $r) {
	// 		$this->db->rollBack();
    //         return $this->MakeJsonResponse(203, "unknown error", "");
    //     }
    // }
    function applyLoan($data, $office) {
        try {
            if (empty($data['cid']) || empty($data['product_id']) || empty($data['loanofficer']) || empty($data['principal']) || empty($data['guarantor1'])) {
                return $this->MakeJsonResponse(206, "Fill all required fields", "");
            }

            $rs = $this->GetClientDetails($data['cid']);
            if (sizeof($rs) == 0) {
                return $this->MakeJsonResponse(404, "Client not found", "");
            }

            // $loan_acc = $this->LoanAccount();
            $id = $data['product_id'];
            $loan_product = $this->getloanProducts($id, $office);
            $loan_productid = $loan_product[0]['id'];
            $rsInfo = $this->GetClientLoanProduct($data['cid'], $loan_productid);
            if (sizeof($rsInfo) > 0) {
                return $this->MakeJsonResponse(200, "Member has pending loan product", "");
            }
            if (sizeof($loan_product) == 0) {
                return $this->MakeJsonResponse(204, "Loan product not found", "");
            }

            $principal = str_replace(',', '', $data['principal']);

            $min_principal_amount = $loan_product[0]['min_principal_amount'];
            $max_principal_amount = $loan_product[0]['max_principal_amount'];

            if ($principal < $min_principal_amount || $principal > $max_principal_amount) {
                return $this->MakeJsonResponse(205, "Loan out of range", "");
            }

            $this->db->beginTransaction();

            if (isset($data['group_id'])) {
                $group_id = $data['group_id'];
            } else {
                $group_id = null;
            }

            $submit_date = date('Y-m-d');

            $s_date = date('Y-m-d');
            $created_by = $_SESSION['user_id'];

            $application_fee = $data['application_fee'];
            $disbursement_date = date('Y-m-d', strtotime($data['disbursement_date']));
            $rate = str_replace(',', '', $loan_product[0]['nominal_interest_rate_per_period']);

            $annualRate = $this->val->getAnnualInterest($loan_product[0]['interest_period'], $rate, $days_in_year=null);

            $postData = [
                'sacco_id' => $office, // Get 'office' from function parameter
                'product_id' => $data['product_id'],
                'account_no' => $loan_acc,
                'member_id' => $data['cid'],
                'group_id' => $group_id,
                'submittedon_date' => $submit_date,
                'created_by' => $created_by,
                'submittedon_userid' => $_SESSION['user_id'],
                'loan_officer_id' => $data['loanofficer'],
                'principal_amount_proposed' => $principal,
                'number_of_installments' => str_replace(',', '', $data['duration']),
                'disbursedon_date' => $disbursement_date,
                'nominal_interest_rate_per_period' => $rate,
                'annual_nominal_interest_rate' => $annualRate,
                'loanpurpose' => $loan_product[0]['description'],
                'interest_period' => $loan_product[0]['interest_period'],
                'product_name' => $loan_product[0]['name'],
                'days_in_year' => $loan_product[0]['days_in_year'],
                'installment_option' => $loan_product[0]['installment_option'],
                'interest_method' => $loan_product[0]['interest_method'],
                'duration' => $loan_product[0]['duration'],
                'duration_value' => $data['duration'],
                'grace_period' => $loan_product[0]['grace_period'],
                'grace_period_value' => $loan_product[0]['grace_period_value'],
                'insurance' => $loan_product[0]['insurance'],
                'stamp_duty' => $loan_product[0]['stamp_duty'],
                'guarantor_1' => $data['guarantor1'],
                'guarantor_1_Amount' => $data['guarantor1_amount'],
                'guarantor_2' => $data['guarantor2'],
                'guarantor_2_Amount' => $data['guarantor2_amount'],
                's_consent' => $data['s_consent'],
                'witness' => $data['witnsess'],
                'loan_purpose' => $data['loan_purpose'],
                'frequency' => $loan_product[0]['interest_period'],
            ];

            $prodType = 2;
            $transactionType = 'Loan Application';
            $tran_id = $this->getTransactionID($transactionType);
            $transaction_charges = $this->getTransactionCharges($tran_id);
            $exemptions = $this->getMemberChargeExemptions($data['cid']);
            $total_charge_amount = 0;

            if (!empty($transaction_charges)) {
                foreach ($transaction_charges as $key => $value) {
                    if (is_null($exemptions) || !in_array($value, $exemptions)) {
                        $mapping_charges = $this->GetGLChargePointers($value['id'], $prodType, $transactionType, $tran_id);

                        if (!empty($mapping_charges)) {
                            $debt_id = $mapping_charges[0]["debit_account"];
                            $credit_id = $mapping_charges[0]["credit_account"];

                            $sideA = $this->getAccountSide($debt_id);
                            $sideB = $this->getAccountSide($credit_id);

                            $description = ucfirst($value['name']) . " Charge";

                            $uniq_id = $_SESSION['user_id'] . uniqid();
                            $transaction_id = "L" . $uniq_id;

                            $amount = $value['amount'];

                            $this->makeJournalEntry($debt_id, $office, $_SESSION['user_id'], $uniq_id, $transaction_id, $amount, 'DR', $sideA, $description); // DR
                            $this->makeJournalEntry($credit_id, $office, $_SESSION['user_id'], $uniq_id, $transaction_id, $amount, 'CR', $sideB, $description); // CR

                            $postCharges = [
                                'account_no' => $loan_acc,
                                'charge_id' => $value['id'],
                                'charge_name' => $value['name'],
                                'amount' => $value['amount'],
                            ];

                            $this->db->InsertData('m_loan_charge', $postCharges);

                            $postTransCharges = [
                                'transaction_id' => $transaction_id,
                                'charge_id' => $value['id'],
                                'trans_amount' => $amount,
                                'date' => date("Y-m-d H:i:s"),
                            ];

                            $this->db->InsertData('m_charge_transaction', $postTransCharges);
                        }
                    }
                }
            }

            $loan_id = $this->db->InsertData('m_loan', $postData);

            if (empty($data['group_id']) || is_null($data['group_id']) || $data['group_id'] == "") {
                return [
                    "status" => 100, // Your desired success status code
                    "message" => "Success " . $loan_acc,
                    "redirect_url" => URL . "loans/loanAccountEquiry/" . $loan_acc,
                ];
            } else {
                return [
                    "status" => 100, // Your desired success status code
                    "message" => "Success",
                    "redirect_url" => URL . 'groups/viewgroup/' . $data["group_id"] . '?actno=' . $loan_acc,
                ];
            }
        } catch (Exception $r) {
            $this->db->rollBack();
            return [
                "status" => 203, // Your desired error status code
                "message" => "Unknown error",
            ];
        }
    }

    function getloanProductapp($id)
    {
        $product_id = 0;

        $office = $_SESSION['office'];
        $rset = [];
        $client_pdt = $this->db->SelectData("SELECT DISTINCT(product_id) AS product_id FROM  m_loan where member_id='" . $id . "' ");
        $client = $this->db->selectData("SELECT * FROM members WHERE c_id='" . $id . "' and office_id='" . $office . "'");

        $no = count($client_pdt);
        $clent_no = count($client);

        $add = null;
        if ($no > 0) {
            for ($i = 0; $i < $no; $i++) {
                $add .= " AND id!='" . $client_pdt[$i]["product_id"] . "'";
            }

            $pdts = $this->db->SelectData("SELECT * FROM m_product_loan where office_id = $office and status='open' $add");
            if (count($pdts) > 0) {
                foreach ($pdts as $key => $value) {
                    $pointers = $this->db->SelectData("SELECT * FROM acc_gl_pointers P JOIN  transaction_type T ON P.transaction_type_id=T.transaction_type_id where T.product_type='2' AND P.product_id='" . $value['id'] . "'");

                    array_push($rset, [
                        'id' => $value['id'],
                        'name' => $value['name'],
                    ]);
                }
                echo json_encode(["result" => $rset]);
            } else {
                die();
            }
        } else {
            $pdts = $this->db->SelectData("SELECT * FROM m_product_loan where office_id = $office and status='open'");
            foreach ($pdts as $key => $value) {
                $pointers = $this->db->SelectData("SELECT * FROM acc_gl_pointers P JOIN  transaction_type T ON P.transaction_type_id=T.transaction_type_id where T.product_type='2' AND P.product_id='" . $value['id'] . "'");
                //if(count($pointers)>5){
                array_push($rset, [
                    'id' => $value['id'],
                    'name' => $value['name'],
                ]);
                //}
            }
            echo json_encode(["result" => $rset]);
            die();
        }
    }

    function loanchargeApplication($id)
    {
        $office = $_SESSION['office'];
        $query = $this->db->selectData("SELECT * FROM m_charge WHERE charge_applies_to = 2 AND status = 'Active' AND transaction_type_id = '3' AND office_id = '$office'");

        //$query= $this->db->SelectData("SELECT * FROM m_product_loan_charge mp JOIN m_charge mc where mp.charge_id =mc.id  and mc.charge_applies_to ='Loan'  and mp.product_loan_id ='".$id."' order by mp.charge_id desc");

        $option = "";
        if (empty($query)) {
            return '';
        }
        if (count($query) > 0) {
            foreach ($query as $key => $value) {
                $option .= '<tr><td>' . $value['id'] . '</td><td>' . $value['name'] . '</td><td>' . number_format($value['amount'], 2) . '</td><tr>';
            }
            print_r($option);
            die();
        } else {
        }
        return $query;
    }

    function getLoanApplicationFees()
    {
        $office = $_SESSION['office'];
        $charges = $this->db->selectData("SELECT * FROM m_charge WHERE charge_applies_to = 2 AND status = 'Active' AND transaction_type_id = '3' AND office_id = '$office'");

        $total = 0;
        foreach ($charges as $key => $value) {
            $total += $value['amount'];
        }

        echo $total;
        return $total;
    }

    function getApplicationFees($id)
    {
        $query = $this->db->SelectData(
            "SELECT * FROM m_product_loan_charge mp JOIN m_charge mc 

		where mp.charge_id =mc.id  and mc.charge_applies_to ='Loan'  and mp.product_loan_id ='" .
                $id .
                "' AND charge_time='1'"
        );
        echo $query[0]['amount'];
        return $query;
    }

    function m_loan_charge($id)
    {
        $chargedetails = $this->db->SelectData("SELECT * FROM m_loan_charge where account_no ='" . $id . "' ");
        return $chargedetails;
    }

    function insurance_stampduty($id)
    {
        $loandetails = $this->loandetails($id);
        if (empty($loandetails)) {
            return '';
        } else {
            $insurance_charge = 0;
            $Stampduty_charge = 0;

            $postData3 = [];

            //insurance_charge insurance
            $amount = $loandetails[0]['approved_principal'];
            $insurance_charge = ($amount * $loandetails[0]['insurance']) / 100;
            //Stampduty_charge
            $Stampduty_charge = ($amount * $loandetails[0]['stamp_duty']) / 100;
            $balanceAfterCharge = $amount - ($insurance_charge + $Stampduty_charge);

            $postData3['original_disbursement_amount'] = $amount;
            $postData3['insurance_charge'] = $insurance_charge;
            $postData3['stamp_duty_charge'] = $Stampduty_charge;
            $postData3['amount_to_be_disbursed_after_charges'] = $balanceAfterCharge;

            return $postData3;
        }
    }

    function loanProductCollateral($id)
    {
        $query = $this->db->SelectData("SELECT * FROM loan_product_collateral where loan_product_id='" . $id . "'");

        print_r(json_encode($query));
        die();
    }

    function loan_tobe_Paid($id)
    {
        $results = $this->db->SelectData("SELECT * FROM m_loan l JOIN members m ON l.member_id=m.c_id  where l.account_no='" . $id . "' ");

        if (empty($results)) {
            $results1 = $this->db->SelectData("SELECT * FROM m_loan l JOIN m_group m ON l.group_id=m.id  where l.account_no='" . $id . "' ");

            if (empty($results1)) {
                return "";
            } else {
                return $results1;
            }
        } else {
            return $results;
        }
    }
    function loandetails($id)
    {
        $results = $this->db->SelectData("SELECT * FROM m_loan where account_no='" . $id . "' ");
        if (empty($results)) {
            return "";
        } else {
            return $results;
        }
    }
    function getClient($id)
    {
        $results = $this->db->SelectData("SELECT * FROM members c INNER JOIN m_branch b where c.office_id  = b.id AND c.c_id='" . $id . "'  order by c.c_id desc");
        if (empty($results)) {
            return '';
        } else {
            return $results;
        }
    }

    function m_loan_collateral($id)
    {
        return $this->db->SelectData("SELECT * FROM m_loan_collateral where account_no='" . $id . "' ");
    }
    function m_loan_collat($id)
    {
        $result = $this->db->SelectData("SELECT * FROM m_loan_collateral where account_no='" . $id . "' ");
        if (count($result) > 0) {
            $rset = [];
            foreach ($result as $key => $value) {
                array_push($rset, [
                    'id' => $result[$key]['id'],
                    'colleteral_id' => $result[$key]['name'],
                ]);
            }

            echo json_encode(["result" => $rset]);
            die();
        }
    }

    function collateral($id)
    {
        return $this->db->SelectData("SELECT * FROM loan_product_collateral where  	loan_product_id='" . $id . "' ");
    }

    function m_loan_collateral_details($id)
    {
        $results = $this->db->SelectData("SELECT * FROM m_loan_collateral where account_no='" . $id . "' ");

        if (empty($results)) {
            return '';
        } else {
            foreach ($results as $key => $value) {
                $colleteral_id = $value['name'];
                $collt = $this->db->SelectData("SELECT * FROM loan_product_collateral where collateral_id='" . $colleteral_id . "' ");

                $rset[$key]['id'] = $value['id'];
                if (!empty($collt)) {
                    $rset[$key]['name'] = $collt[0]['collateral_name'];
                }
            }

            return $rset;
        }
    }

    function loanProductCollateralview($id)
    {
        $query = $this->db->SelectData("SELECT * FROM loan_product_collateral where loan_product_id='" . $id . "'");
        $option = '';
        if (count($query) > 0) {
            foreach ($query as $key => $value) {
                $option .= '     ' . $value['collateral_id'] . '      ' . $value['collateral_name'] . '<br/> ';
            }
        }
        print_r($option);
        die();
    }

    function getGuarantor($id)
    {
        $office = $_SESSION['office'];

        $query = $this->db->SelectData("SELECT * FROM members c JOIN m_branch b WHERE c.office_id = b.id AND c.status ='Active' AND c.office_id='" . $office . "' AND c.c_id !='" . $id . "' order by c.c_id desc");

        $count = count($query);
        if ($count > 0) {
            foreach ($query as $key => $value) {
                $account = $this->getAccountNo($query[$key]['c_id']);

                if (empty($query[$key]['company_name'])) {
                    $rset[$key]['name'] = $query[$key]['firstname'] . " " . $query[$key]['middlename'] . " " . $query[$key]['lastname'];
                } else {
                    $rset[$key]['name'] = $query[$key]['company_name'];
                    $rset[$key]['incorporation_date'] = $query[$key]['incorporation_date'];
                    $rset[$key]['incorporation_expiry'] = $query[$key]['incorporation_expiry'];
                    $rset[$key]['incorporation_no'] = $query[$key]['incorporation_no'];
                    $rset[$key]['business_line'] = $query[$key]['business_line'];
                }

                $rset[$key]['accountno'] = $account;
                $rset[$key]['c_id'] = $query[$key]['c_id'];
                $rset[$key]['status'] = $query[$key]['status'];
                $rset[$key]['status_code'] = $query[$key]['status_code'];
                $rset[$key]['office'] = $query[$key]['name'];
            }

            return $rset;
        }
    }

    function get_client_loandetails($id)
    {
        $office = $_SESSION['office'];
        $result = $this->db->SelectData("SELECT * FROM m_loan l INNER JOIN members c, m_product_loan mp  where l.member_id  = c.c_id AND c.office_id = '" . $office . "' AND  l.product_id  = mp.id AND l.account_no='" . $id . "' ");
        if (count($result) > 0) {
            $cid = $result[0]['member_id'];
            if (!empty($result[0]['company_name'])) {
                $displayname = $result[0]['company_name'];
            } else {
                $displayname = $result[0]['firstname'] . " " . $result[0]['middlename'] . " " . $result[0]['lastname'];
            }
            $rset = [];
            foreach ($result as $key => $value) {
                array_push($rset, [
                    'member_id' => $result[$key]['member_id'],
                    'displayname' => $displayname,
                    'dob' => $result[0]['date_of_birth'],
                    'national_id' => $result[0]['national_id'],
                    'address' => $result[0]['address'],
                    'productname' => $result[0]['name'],
                    'Guarantor1' => $result[0]['guarantor_1'],
                    'Guarantor2' => $result[0]['guarantor_2'],
                    'account_number' => $result[0]['account_no'],
                    'account_no' => $result[0]['account_no'],
                    'status' => $result[0]['loan_status'],
                    'product_id' => $result[0]['product_id'],
                ]);
            }

            print_r(json_encode(["result" => $rset]));
            die();
        } else {
            $result = $this->db->SelectData("SELECT * FROM m_loan l INNER JOIN m_group c, m_product_loan mp  where l.group_id  = c.id AND c.office_id = '" . $office . "' AND  l.product_id  = mp.id AND l.account_no='" . $id . "' ");
            if (count($result) > 0) {
                $cid = $result[0]['group_id'];
                $displayname = $result[0][63];

                //print_r($result);
                //die();
                $rset = [];
                foreach ($result as $key => $value) {
                    array_push($rset, [
                        'member_id' => $cid,
                        'displayname' => $displayname,
                        'productname' => $result[0]['name'],
                        'status' => $result[0]['loan_status'],
                        /*'dob'=>$result[0]['date_of_birth'],
					'national_id'=>$result[0]['national_id'],
					'address'=>$result[0]['address'],
					'Guarantor1'=>$result[0]['guarantor_1'],
					'Guarantor2'=>$result[0]['guarantor_2'],
					'account_number'=>$result[0]['account_no'],
					'account_no'=>$result[0]['account_no'],
					'product_id'=>$result[0]['product_id'],*/
                    ]);
                }

                print_r(json_encode(["result" => $rset]));
                die();
            } else {
                $rset = [];
                array_push($rset, [
                    'member_id' => '0',
                ]);
                print_r(json_encode(["result" => $rset]));
                die();
            }
        }
    }

    function updateNewclientLoanproduct()
    {
        //$loan_acc= $this->LoanAccount();
        $data = $_POST;
        $account_no = $data['account_no'];

        $loan_result = $this->db->SelectData("SELECT * FROM m_loan where account_no='" . $account_no . "' ");
        if (empty($data['product_id'])) {
            $data['product_id'] = $loan_result[0]['product_id'];
        }

        $loan_product = $this->getloanProducts($data['product_id']);
        $loan_productid = $loan_product[0]['id'];

        $approvedon_date = date('Y-m-d');

        $s_date = date('Y-m-d');
        $created_by = $_SESSION['user_id'];

        $repaymentsStartingFromDate = strtotime($data['repaymentsStartingFromDate']);

        $r_Date = date('Y-m-d', $repaymentsStartingFromDate);
        $disbursement_date = date('Y-m-d', strtotime($data['disbursement_date']));

        $postData = [
            'product_id' => $data['product_id'],
            'member_id' => $data['c_id'],
            'approvedon_userid' => $_SESSION['user_id'],
            'approvedon_date' => $approvedon_date,
            'principal_amount_proposed' => str_replace(',', '', $data['principal']),
            'number_of_installments' => str_replace(',', '', $data['number_of_installments']),
            'expected_firstrepaymenton_date' => $r_Date,
            'disbursedon_date' => $disbursement_date,
            //'nominal_interest_rate_per_period' => str_replace( ',', '', $data['nominalinterestrate']),
            'nominal_interest_rate_per_period' => str_replace(',', '', $loan_product[0]['nominal_interest_rate_per_period']),
            'annual_nominal_interest_rate' => str_replace(',', '', $loan_product[0]['nominal_interest_rate_per_period']),
            'loanpurpose' => $loan_product[0]['description'],
            'product_name' => $loan_product[0]['name'],
            'days_in_year' => $loan_product[0]['days_in_year'],
            'installment_option' => $loan_product[0]['installment_option'],
            'interest_method' => $loan_product[0]['interest_method'],
            'duration' => $loan_product[0]['min_duration'],
            'duration_value' => $data['duration'],
            'grace_period' => $loan_product[0]['grace_period'],
            'grace_period_value' => $loan_product[0]['grace_period_value'],
            'insurance' => $loan_product[0]['insurance'],
            'stamp_duty' => $loan_product[0]['stamp_duty'],
            'guarantor_1' => $data['guarantor1'],
            'guarantor_2' => $data['guarantor2'],
            's_consent' => $data['s_consent'],
            'witness' => $data['witnsess'],
        ];

        $this->db->DeleteData('m_loan_collateral', "`account_no` = '{$account_no}'");

        $num_collateral = count($data['collateral']);
        $num_collaterals = $data['collateral'];

        for ($i = 0; $i < $num_collateral; $i++) {
            if (!empty($num_collaterals[$i])) {
                $collateral = [
                    'account_no' => $account_no,
                    'name' => $num_collaterals[$i],
                ];
                $this->db->InsertData('m_loan_collateral', $collateral);
            }
        }

        //$account_no =$this->db->InsertData('m_loan', $postData);
        $this->db->UpdateData('m_loan', $postData, "`account_no` = '{$account_no}'");

        //Equal installment Flat interest
        $status = 'Updated';
        header('Location: ' . URL . 'loans/modifyloanaplication/' . $account_no . '?msg=' . $status . '');
    }

    function updateloancolleteral()
    {
        $data = $_POST;

        $account_no = $data['account_no'];
        $product_id = $data['product_id'];

        $num_collateral = count($data['collateral']);
        //print_r($data['collateral']);
        $num_collaterals = $data['collateral'];

        $this->db->DeleteData('m_loan_collateral', "`account_no` = '{$account_no}'");

        for ($i = 0; $i < $num_collateral; $i++) {
            if (!empty($num_collaterals[$i])) {
                $collateral = [
                    'account_no' => $account_no,
                    'name' => $num_collaterals[$i],
                ];
                $this->db->InsertData('m_loan_collateral', $collateral);
            }
        }

        $status = 'Updated';

        header('Location: ' . URL . 'loans/maintaincollateral/' . $account_no . '?msg=' . $status . '');
    }

    function clientsloanList($status)
    {
        $results = $this->db->SelectData("SELECT * FROM m_loan l INNER JOIN  m_product_loan p, members c WHERE l.product_id=p.id AND l.member_id=c.c_id AND l.loan_status='" . $status . "'");

        return $results;
    }

    function approvedLoan()
    {
        $data = $_POST;
        $status = $data['approve'];

        $id = $data['account_no'];
        $loandetails = $this->loandetails($id);
        $sts = $loandetails[0]['loan_status'];
        if ($sts == $status) {
            $status = 'Already  ' . $status;
            header('Location: ' . URL . 'loans/backofficeapproveloan?msg=' . $status . '');
            die();
        } else {
            $d = strtotime($data['date']);
            $date = date('Y-m-d', $d);
            $postData = [
                'approvedon_date' => $date,
                'approvedon_userid' => $_SESSION['user_id'],
                'loan_status' => $status,
                'approved_principal' => str_replace(',', '', $data['proposed_amount']),
            ];

            $this->db->UpdateData('m_loan', $postData, "`account_no` = '{$id}'");
            // Equal installment Flat interest

            header('Location: ' . URL . 'loans/backofficeapproveloan?msg=' . $status . '');
        }
    }

    function disburs($id)
    {
        $loandetails = $this->loandetails($id);

        //$charges = $this->m_loan_charge($loandetails[0]['product_id']);
        $chargedetails = $this->db->SelectData("SELECT * FROM m_loan_charge where account_no ='" . $id . "' ");

        //$clientsavingdetails = $this->getClientSaveddetailsid($data['savings_account']);
        //$chargedetails = $this->getloanapplicationcharges($account_no);

        $charge = 0;
        $insurance_charge = 0;
        $Stampduty_charge = 0;

        $postData3 = [];
        //if(!empty($debit_acc[$i])){

        foreach ($chargedetails as $key => $value) {
            //if(($value['charge_applies_to']==1)&&($value['charge_time']==1)){
            $postData3['charge_name'] = $value['charge_name'];
            $postData3['charge_amount'] = $value['amount'];
            //if($value['charge_calculation_enum']==1){
            $charge = $charge + $value['amount'];
            $charge_id = $value['id'];
            $charge_name = $value['charge_name'];
            // }
        }
        //insurance_charge insurance
        $amount = $loandetails[0]['approved_principal'];
        $insurance_charge = $amount * $loandetails[0]['insurance'];
        //Stampduty_charge
        $Stampduty_charge = $amount * $loandetails[0]['stamp_duty'];
        $balanceAfterCharge = $amount - ($charge + $insurance_charge + $Stampduty_charge);
        $postData3['total_charge'] = $charge;

        $postData3['original_disbursement_amount'] = $amount;
        $postData3['insurance_charge'] = $insurance_charge;
        $postData3['stamp_duty_charge'] = $Stampduty_charge;
        $postData3['amount_to_be_disbursed_after_charges'] = $balanceAfterCharge;

        return $postData3;
    }

    function getClientSavingsdDetails($id)
    {
        return $this->db->SelectData(
            "SELECT * FROM m_savings_product p INNER JOIN m_savings_account s

		where s.product_id = p.id and  s.member_id='" .
                $id .
                "' order by s.id DESC"
        );
    }

    function getWallet($id)
    {
        return $this->db->SelectData("SELECT * FROM sm_mobile_wallet where member_id='" . $id . "' ");
    }

    function getSavingsAccounts($id)
    {
        return $this->db->SelectData("SELECT * FROM m_savings_account where member_id='" . $id . "' ");
    }

    function makeJournalEntry($acc_id, $office, $user, $loan_trans_id, $trans_id, $amount, $type, $side, $description)
    {
        $postData = [
            'account_id' => $acc_id,
            'office_id' => $office,
            'branch_id' => $_SESSION['branchid'],
            'createdby_id' => $user,
            'loan_transaction_id' => $loan_trans_id,
            'transaction_id' => $trans_id,
            'amount' => $amount,
            'transaction_type' => $type,
            'trial_balance_side' => $side,
            'description' => $description,
        ];

        $this->db->InsertData('acc_gl_journal_entry', $postData);
    }

    function loandisbursal($data)
    {
        try {
            $prodType = 2;
            $transactionType = 'Loan Disbursement';

            $account_no = $data['account_no'];
            $save_id = $data['savings_account'];
            $loandetails = $this->loandetails($account_no);

            $product_id = $loandetails[0]['product_id'];
            $office_id = $loandetails[0]['loan_officer_id'];
            $stats = $loandetails[0]['loan_status'];
            $amount = $loandetails[0]['approved_principal'];

            if ($stats != 'Approved') {
                $status = "Loan already " . $stats;
                if ($stats == 'Pending') {
                    $status = $stats;
                }
                return $this->MakeJsonResponse(101, "product not available", "");
            }
			$this->db->beginTransaction();

            $savings_account_no = $save_id;

            $chargedetails = $this->m_loan_charge($account_no);

            $insurance_charge = 0;
            $Stampduty_charge = 0;

            $client_details = $this->getClient($data['clientid']);
            if (empty($client_details)) {
                $office_id = $_SESSION['office'];
            } else {
                $office_id = $client_details[0]['office_id'];
            }

            $dd = date('Y-m-d');
            $update_time = date('Y-m-d H:i:s');

            $transaction_uniqid = uniqid();
            $name = null;

            $updateloan = [
                'loan_status' => 'Disbursed',
                'savings_account_no' => $savings_account_no,
                'principal_disbursed' => $amount,
                'disbursedon_date' => $dd,
                'last_updated_on' => $update_time,
                'total_outstanding' => $amount,
            ];

            $this->db->UpdateData('m_loan', $updateloan, "`account_no` = '{$account_no}'");
            $rsSchedule = $this->loans_calculations->repaymentCalculations($account_no);
		
			if(!$rsSchedule){
				$this->db->rollBack();
				return $this->MakeJsonResponse(102, "error generating schedule");
			}

            if ($data['disburseto'] == 'savings') {
                $save = $this->tr->SubmitSavingsAccountTransaction($savings_account_no, $amount, $product_id, $prodType, "CR", $transactionType, "Loan-Disbursement", "Loan potifolio", "auth cr");
                if ($save['status'] != 100) {
					$this->db->rollBack();
                    return $save;
                } else {
                    $transaction_id = $save['data'];
                }
            } else {
				$this->db->rollBack();
                return $this->MakeJsonResponse(102, "no disburse method available");
            }

            $loan_transaction = [
                'office_id' => $office_id,
                'account_no' => $account_no,
                'transaction_date' => $update_time,
                'amount' => $amount,
                'outstanding_loan_balance_derived' => $amount,
                'appuser_id' => 0,
                'transaction_type' => 'Debit',
                'transaction_id' => $transaction_id,
                'm_description' => 'Opening Balance',
            ];

            $loan_transaction_id = $this->db->InsertData('m_loan_transaction', $loan_transaction);

            $disburseDetails = [
                'account_no' => $account_no,
                'disbursedon_date' => date('Y-m-d'),
                'expected_disburse_date' => date('Y-m-d', strtotime($loandetails[0]['disbursedon_date'])),
                'principal' => $amount,
            ];

            $this->db->InsertData('m_loan_disbursement_detail', $disburseDetails);

            $tran_id = $this->getTransactionID($transactionType);

            $transaction_charges = $this->getLoanProductTransactionCharges($product_id, $tran_id);
            $exemptions = $this->getMemberChargeExemptions($data['clientid']);
            $total_charge_amount = 0;

            if (!empty($transaction_charges)) {
                foreach ($transaction_charges as $key => $value) {
                    if (is_null($exemptions) || !in_array($value, $exemptions)) {
                        $mapping_charges = $this->GetGLChargePointers($value['id'], $prodType, $transactionType, $tran_id);
                        if (!empty($mapping_charges)) {
                            $charge_calculation_enum = $value['charge_calculation_enum'];
                            if ($charge_calculation_enum == 2) {
                                $charge_amount = ($amount * $value['amount']) / 100;
                            } else {
                                $charge_amount = $value['amount'];
                            }
                            $total_charge_amount += $charge_amount;

                            $chargeName = $transaction_charges[0]['name'];

                            $saveCharge = $this->tr->SubmitSavingsAccountTransaction($savings_account_no, $charge_amount, $value['id'], 6, "DR", $transactionType, $chargeName, $chargeName, "auto dr");

                            if ($saveCharge['status'] == 100) {
                                $postTransCharges = [
                                    'transaction_id' => $transaction_id,
                                    'charge_id' => $value['id'],
                                    'trans_amount' => $charge_amount,
                                    'date' => date("Y-m-d H:i:s"),
                                ];
                                $this->db->InsertData('m_charge_transaction', $postTransCharges);
                            }
                        }
                    }
                }
            }
			$this->db->commit();
            return $this->MakeJsonResponse(100, "Loan disbursed successfully", "#");
        } catch (Exception $r) {
			$this->db->rollBack();
            return $this->MakeJsonResponse(203, "unknown error", "");
        }
    }

    function all_m_loan_repayment_schedule_detail($id)
    {
        $results = $this->db->SelectData("SELECT * FROM m_loan_repayment_schedule  where account_no='" . $id . "'   order by id ");
        return $results;
    }

    function GetWalletTransactionBalance($wallet_account)
    {
        return $this->db->SelectData(
            "SELECT * FROM sm_mobile_wallet
		WHERE wallet_account_number=:tid",
            ['tid' => $wallet_account]
        );
    }

    function getPaymentNo($id)
    {
        $results = $loan_details = $this->db->SelectData("SELECT * FROM m_loan_repayment_schedule  where account_no='" . $id . "' and principal_amount  !=principal_completed order by id ");
        if (empty($results)) {
            return "";
        } else {
            return $results;
        }
    }

    function PaymentMadeLast($id)
    {
        $last_id = $this->db->SelectData("SELECT *  FROM m_loan_repayment_schedule  where account_no='" . $id . "' and principal_amount  !=principal_completed order by id  ");

        if (!empty($last_id)) {
            return $last_id;
        } else {
            return "";
        }
    }
    function getlastPaymentNo($id)
    {
        $first_id = $this->db->SelectData("SELECT * FROM m_loan_repayment_schedule  where account_no='" . $id . "'  and principal_amount  !=principal_completed order by id ");

        if (!empty($first_id)) {
            return $first_id;
        } else {
            return "";
        }
    }

    function getspecifiedPaymentdetails($id2)
    {
        $repayment = $this->db->SelectData("SELECT * FROM m_loan_repayment_schedule  where id='" . $id2 . "' ");
    }

    function getlastPaymentdetails($id)
    {
        $rs = $this->db->SelectData("SELECT * FROM m_loan_repayment_schedule  where account_no='" . $id . "' order by id desc LIMIT 1");
        return $rs;
    }

    function updaterepaymentscheduletable($schedule_id ,$account_no,$schedule,$trans_id,$amount,$scheduleBalance){
	 $track_repayment = $this->db->SelectData("SELECT * FROM  m_loan_repayment_calc where schedule_id = '$schedule_id' AND account_no = '".$account_no."' AND trans_id = '".$trans_id."' ");
	 $value = $track_repayment[0];
		 
		 $principal = $value['principal'];
		 $interest = $value['interest'];
		 $fees = $value['fees'];
		 $penalty = $value['penalty'];
		 
 		
		
		$principal = $principal+$schedule['principal_completed'];
		$interest = $interest+$schedule['interest_completed'];
		$fees = $fees+$schedule['fee_charges_completed'];
		$penalty = $penalty+$schedule['penalty_charges_completed_derived'];
		
		  
		if ($amount >= $scheduleBalance) {
			$status = 'true';
		}else{
			$status = 'false';
		}
		
        $dd = date('Y-m-d');
		
		 $updaterepaymentschedul = [
            'completed' => $status,
            'principal_completed' => $principal,
            'interest_completed' => $interest,
            'fee_charges_completed' => $fees,
            'penalty_charges_completed_derived' => $penalty,
            'obligations_met_on_date' => $dd,
        ];
         
		
	 $this->db->UpdateData('m_loan_repayment_schedule', $updaterepaymentschedul, "`id` = '{$schedule_id}'");
		
		return true;
	}
 

	function m_loan_repayment_calc($trans_id,$account_no, $schedule_id, $principal, $interest,$fees,$penalty){
        $this->db->beginTransaction();
		 $track_repayment = $this->db->SelectData("SELECT * FROM  m_loan_repayment_calc where trans_id = '$trans_id' AND account_no = '".$account_no."' AND schedule_id = '$schedule_id' order by id desc ");
		 
			$transaction_postData = [
            'principal' => $principal,
            'trans_id' => $trans_id,
            'interest' => $interest,
            'fees' => $fees,
            'penalty' => $penalty,
            'account_no' => $account_no,
            'schedule_id' => $schedule_id,
        ];
	
		if(sizeof($track_repayment) == 0){
			$this->db->InsertData('m_loan_repayment_calc', $transaction_postData);
		}else{
		$id = $track_repayment[0]['id'];
		$this->db->UpdateData('m_loan_repayment_calc', $transaction_postData, "`id` = '{$id}'");
		}
		$this->db->commit();
	return $transaction_postData;

    }
	
	function CompleteLoanRepayment($account_no,$trans_id){
$track_repayment = $this->db->SelectData("SELECT sum(interest) as interest, sum(penalty) as penalty, sum(principal) as principal, sum(fees) as fees  FROM  m_loan_repayment_calc where trans_id = '$trans_id' AND account_no = '".$account_no."'  order by id desc ");

print_r($track_repayment ); die();
	}
	
	  
	
	function GetIncompleteLoanSchedule($id){
return $this->db->SelectData("SELECT * FROM m_loan_repayment_schedule  where account_no='" . $id . "' AND completed = 'false'   order by id ");

	}
   function amountRedistribution($account_no,$loanrepaymentamount){
	   $trans_id = uniqid();
	   $scheduleArray = $this->GetIncompleteLoanSchedule($account_no);
	   foreach($scheduleArray as $key=>$schedule){
		   //echo $loanrepaymentamount."--";
	   if ($loanrepaymentamount > 0) {
                $schedule_id = $schedule['id'];
				$penaltyAmount = $schedule['penalty_charges_amount'];
				$penaltyAmountPaid = $schedule['penalty_charges_completed_derived'];
				$penaltyBalance = $penaltyAmount - $penaltyAmountPaid;
				
				$feesAmount = $schedule['fee_charges_amount'];
				$feesAmountPaid = $schedule['fee_charges_completed'];
				$feesBalance = $feesAmount - $feesAmountPaid;
				
				$interestAmount = $schedule['interest_amount'];
				$interestAmountPaid = $schedule['interest_completed'];
				$interestBalance = $interestAmount - $interestAmountPaid;
				
				$principleAmount = $schedule['principal_amount'];
				$principleAmountPaid = $schedule['principal_completed'];
				$principleBalance = $principleAmount - $principleAmountPaid;
				
	
                 $scheduleBalance = $principleBalance + $interestBalance+$penaltyBalance;
				

                if ($loanrepaymentamount >= $scheduleBalance) {
	$this->m_loan_repayment_calc($trans_id,$account_no, $schedule_id, $principleBalance, $interestBalance,$feesBalance,$penaltyBalance);
	
	 
                    $loanrepaymentamount -= $scheduleBalance;
                } else {
	$paidprincipleForThisSchedule =  $paidinterestForThisSchedule = $paidFeesForThisSchedule = $paidPenaltyForThisSchedule = $TotalpaidPenalty = $TotalpaidFees  = 0;
					
		//1 clear panalty 
		if ($loanrepaymentamount >0 && $penaltyBalance>0 ) {
		if ($loanrepaymentamount >= $penaltyBalance) {
		$TotalpaidPenalty = $penaltyAmount;
		$paidPenaltyForThisSchedule = $penaltyBalance;
		}else{
		$TotalpaidPenalty = $penaltyAmountPaid + $loanrepaymentamount;
		$paidPenaltyForThisSchedule = $loanrepaymentamount;
		}
	$this->m_loan_repayment_calc($trans_id,$account_no, $schedule_id, 0, 0,0,$paidPenaltyForThisSchedule);
			
		$loanrepaymentamount  =  $loanrepaymentamount - $paidPenaltyForThisSchedule;
		}
		
		//2 clear fees 
		if ($loanrepaymentamount >0 && $feesBalance>0 ) {
		if ($loanrepaymentamount >= $feesBalance ) {
		$TotalpaidFees = $feesAmount;
		$paidFeesForThisSchedule = $feesBalance;
		}else{
		$TotalpaidFees = $feesAmountPaid + $loanrepaymentamount;
		$paidFeesForThisSchedule = $loanrepaymentamount;
		}
		
		$this->m_loan_repayment_calc($trans_id,$account_no, $schedule_id, 0, 0,$paidFeesForThisSchedule,$paidPenaltyForThisSchedule);
		
		
		$loanrepaymentamount  =  $loanrepaymentamount - $paidFeesForThisSchedule;
		}
		//3 clear interest 
		 
		if ($loanrepaymentamount >0 && $interestBalance>0 ) {
		if ($loanrepaymentamount >= $interestBalance) {
		$Totalpaidinterest = $interestAmount;
		$paidinterestForThisSchedule = $interestBalance;
		}else{
		$Totalpaidinterest = $interestAmountPaid + $loanrepaymentamount;
		$paidinterestForThisSchedule = $loanrepaymentamount;
		}
		
	$this->m_loan_repayment_calc($trans_id,$account_no, $schedule_id, 0, $paidinterestForThisSchedule,$paidFeesForThisSchedule,$paidPenaltyForThisSchedule);
		
				
		$loanrepaymentamount  =  $loanrepaymentamount - $paidinterestForThisSchedule;
		
		}
		
		
		
		if ($loanrepaymentamount >0 && $principleBalance>0 ) {
		$Totalpaidprinciple = $principleAmountPaid + $loanrepaymentamount;
		$paidprincipleForThisSchedule = $loanrepaymentamount;
		
			$this->m_loan_repayment_calc($trans_id,$account_no, $schedule_id, $paidprincipleForThisSchedule, $paidinterestForThisSchedule,$paidFeesForThisSchedule,$paidPenaltyForThisSchedule);
			
		$loanrepaymentamount  =  0;
		}
				}
		
		$this->updaterepaymentscheduletable($schedule_id, $account_no,$schedule, $trans_id,$loanrepaymentamount, $scheduleBalance);
	
	   }

   }
   return $trans_id;
}




   
    function loanrepayment($data){
	try{
		
            $account_no = $data['account_no'];
            $transaction_amount = str_replace(",", "", $data['transaction_amount']);
            $remaining_bal = 0;
			//step1 GetLoan
			$dd = date('Y-m-d');
            $loandetails = $this->loandetails($account_no);
            $office_id = $loandetails[0]['loan_officer_id'];
            $loanbalance = $loandetails[0]['total_outstanding'];
			$savings_account_number = $loandetails[0]['savings_account_no'];
            $status = $loandetails[0]['loan_status'];
            $product_id = $loandetails[0]['product_id'];
            if ($status == 'Closed') {
                return $this->MakeJsonResponse(404, "loan closed");
            }
			
			
            $checkresults = $this->PaymentMadeLast($account_no);
            $installment = $checkresults[0]["installment"];
            $interest_rep_db = $checkresults[0]["interest_amount"];
            $interest_completed_db = $checkresults[0]["interest_completed"];
            $remaining_interest_db = $interest_rep_db - $interest_completed_db;
			
			
            if ($transaction_amount < $remaining_interest_db) {
                $status = 'Transaction not successful less amount ';
                return $this->MakeJsonResponse(101, $status);
            }


			
			
            $lastpaymentdetails = $this->getlastPaymentdetails($account_no);
			
            $checkresults_payment_id = $checkresults[0]['id'];
            $last_payment_id = $lastpaymentdetails[0]['id'];
            $last_payment_principle = $lastpaymentdetails[0]['principal_amount'];
            $last_payment_interest = $lastpaymentdetails[0]['interest_amount'];
            $last_payment_principle_completed = $lastpaymentdetails[0]['principal_completed'];
            $last_payment_interest_completed = $lastpaymentdetails[0]['interest_completed'];
			
            if ($checkresults_payment_id == $last_payment_id && $last_payment_principle == $last_payment_principle_completed && $last_payment_interest == $last_payment_interest_completed) {
                return $this->MakeJsonResponse(404, "loan completed");
            }
			

$trans_id = $this->amountRedistribution($account_no, $transaction_amount);
	


$paymentdetails = $this->db->SelectData("SELECT sum(interest) as interest, sum(penalty) as penalty, sum(principal) as principal, sum(fees) as fees  FROM  m_loan_repayment_calc where 
trans_id = '".$trans_id."' AND account_no = '".$account_no."'  ");

$paymentdetails = $paymentdetails[0];
if(!isset($paymentdetails["principal"])){
	return $this->MakeJsonResponse(203, "operation error");
}
$this->db->beginTransaction();
			
if ($data['transaction_type'] == 'direct') {
$cashdata = [
'account_balance' => $this->getUserCashBalance() + $transaction_amount,
];
$this->db->UpdateData('m_staff', $cashdata, "`id` = '{$_SESSION['user_id']}'");
}
			

 
          
            
            $principal_repayment = $paymentdetails["principal"];
            $interest_repayment = $paymentdetails["interest"];
            $fees_repayment = $paymentdetails["fees"];
            $penalty_repayment = $paymentdetails["penalty"];
            $charge = 0;


            $clientsaving_id = $loandetails[0]['savings_account_no'];

            $transaction_uniqid = $trans_id;
       

            if ($data['transaction_type'] == 'savings_act') {
                $s_amount = $transaction_amount;
                $prodType = 2;
                $transactionType = 'Loan Repayment Through Savings';
                $save = $this->tr->SubmitSavingsAccountTransaction($clientsaving_id, $s_amount, $product_id, $prodType, "DR", $transactionType, "Loan-Payment", "CASH", "Loan DR");
                if ($save['status'] != 100) {
					$this->db->rollBack();
                    return $save;
                }
            }

           
$office_id = $_SESSION['office'];
$dd = date('Y-m-d');
$amount = $transaction_amount;

            
			$outstanding_loan_balance = $loanbalance - $principal_repayment;
			if($outstanding_loan_balance<0){
				$overpayment_portion_derived = $outstanding_loan_balance*-1;
				$outstanding_loan_balance = 0;
				
				
                $prodType = 3;
                $transactionType = 'Deposit on Savings';
                $save = $this->tr->SubmitSavingsAccountTransaction($clientsaving_id, $overpayment_portion_derived, $product_id, $prodType, "CR", $transactionType, "Deposit on Savings", "Refund", "Loan OverPayment");
                if ($save['status'] != 100) {
					$this->db->rollBack();
					$save['message'] = "Amount paid is more than loan balance";
                    return $save;
                }
			}
			
            $loan_transaction = [
                'office_id' => $office_id,
                'account_no' => $account_no,
                'transaction_date' => $dd,
                'amount' => $transaction_amount,
                'principal_portion_derived' => $principal_repayment,
                'interest_portion_derived' => $interest_repayment,
                'outstanding_loan_balance_derived' => $outstanding_loan_balance,
                'fee_charges_portion_derived' => $fees_repayment,
                'penalty_charges_portion_derived' => $penalty_repayment,
                'overpayment_portion_derived' => $overpayment_portion_derived,
                'appuser_id' => $_SESSION['user_id'],
                'transaction_type' => 'Credit',
                'm_description' => 'Loan Payment',
                'expected_repayment_date' => $dd,
            ];

            $loan_transaction_id = $this->db->InsertData('m_loan_transaction', $loan_transaction);
            $updateloanBal = [
                'total_outstanding' => $outstanding_loan_balance,
            ];
            $this->db->UpdateData('m_loan', $updateloanBal, "`account_no` = '{$account_no}'");




            if ($outstanding_loan_balance <= 0) {
                $updateloan = [
                    'loan_status' => 'Closed'
                ];
                $this->db->UpdateData('m_loan', $updateloan, "`account_no` = '{$account_no}'");
            }
				
           

            $loanrepaymentamount = $transaction_amount;

            $x = 0;
            $today = date('Y-m-d');
            
			
            $deposit_transaction_id = $loan_transaction_id;
            $deposit_transaction_uniqid = $transaction_uniqid;
            $prodType = 2;

            $transactionType = 'Loan Repayment';
            if ($data['transaction_type'] == 'direct') {
                $mapping = $this->GetGLPointers($product_id, $prodType, $transactionType);
            }

            $tran_id = $this->getTransactionID($transactionType);
            $transaction_charges = $this->getTransactionCharges($tran_id);
            $exemptions = $this->getMemberChargeExemptions($loandetails[0]['member_id']);

            if (!empty($transaction_charges)) {
                foreach ($transaction_charges as $key => $value) {
                    if (is_null($exemptions) || !in_array($value, $exemptions)) {
$mapping_charges = $this->GetGLChargePointers($value['id'], 6, $transactionType, $tran_id);
                        if (!empty($mapping_charges)) {
                            $transaction_amount += $value['amount'];

                            $chargeName = $transaction_charges[0]['name'];

                            $amount = $value['amount'];

                            if ($data['transaction_type'] == 'savings_act') {
                                $saveCharge = $this->tr->SubmitSavingsAccountTransaction($clientsaving_id, $value['amount'], $value['id'], 6, "DR", $transactionType, $chargeName, $chargeName, "Loan DR");

                                if ($saveCharge['status'] == 100) {
                                    $transaction_id = $saveCharge['data'];
                                } else {
                                    $transaction_id = "";
                                }
                            } else {
                                $debt_id = $mapping_charges[0]["debit_account"];
                                $credit_id = $mapping_charges[0]["credit_account"];

                                $sideA = $this->getAccountSide($debt_id);
                                $sideB = $this->getAccountSide($credit_id);

                                $description = ucfirst($value['name']) . " Charge";

                                $uniq_id = $_SESSION['user_id'] . uniqid();
                                $transaction_id = "L" . $uniq_id;

                                $this->makeJournalEntry($debt_id, $_SESSION['office'], $_SESSION['user_id'], $uniq_id, $transaction_id, $amount, 'DR', $sideA, $description); //DR
                                $this->makeJournalEntry($credit_id, $_SESSION['office'], $_SESSION['user_id'], $uniq_id, $transaction_id, $amount, 'CR', $sideB, $description); //CR
                            }

                            if ($transaction_id != null && $transaction_id != "") {
                                $postCharges = [
                                    'account_no' => $account_no,
                                    'charge_id' => $value['id'],
                                    'charge_name' => $value['name'],
                                    'amount' => $value['amount'],
                                ];

                                $this->db->InsertData('m_loan_charge', $postCharges);

                                $postTransCharges = [
                                    'transaction_id' => $transaction_id,
                                    'charge_id' => $value['id'],
                                    'trans_amount' => $amount,
                                    'date' => date("Y-m-d H:i:s"),
                                ];

                                $this->db->InsertData('m_charge_transaction', $postTransCharges);
                            }
                        }
                    } 
                }
            }

            if (!empty($mapping)) {
                $debt_id_principal = $mapping[0]["debit_account"];
                $credit_id_principal = $mapping[0]["credit_account"];
                $sideA = $this->getAccountSide($debt_id_principal);
                $sideB = $this->getAccountSide($credit_id_principal);
                $amount_paid = $transaction_amount;
                $desc = "Loan repayment for Loan account number " . $account_no;
                $this->makeJournalEntry($debt_id_principal, $office_id, $_SESSION['user_id'], $deposit_transaction_uniqid, $deposit_transaction_uniqid, $amount_paid, 'DR', $sideA, $desc); //DR
                $this->makeJournalEntry($credit_id_principal, $office_id, $_SESSION['user_id'], $deposit_transaction_uniqid, $deposit_transaction_uniqid, $amount_paid, 'CR', $sideB, $desc); //CR
            }
			$this->db->commit();
			$status = 'payment successful';
			return $this->MakeJsonResponse(100, $status, "#");
		 
        } catch (Exception $e) {
            $this->db->rollBack();
            $error = $e->getMessage();
            return $this->MakeJsonResponse(203, $error);
        }
    }

    function full_loanrepayment(){
        $this->db->beginTransaction();
        $data = $_POST;
        $office = $_SESSION['office'];
        $account_no = $data['account_no'];
        $transaction_amount = str_replace(",", "", $data['transaction_amount']);

        $loandetails = $this->loandetails($account_no);
        $office_id = $office;
        $lastpaymentdetails = $this->getlastPaymentdetails($account_no);
        $last_payment_id = $lastpaymentdetails[0]['id'];
        $savings_account_number = $loandetails[0]['savings_account_no'];
        $status = $loandetails[0]['loan_status'];
        $client_name = $this->getMemberName($loandetails[0]['member_id']);
        $checkresults = $this->PaymentMadeLast($account_no);
        $installment = $checkresults[0]["installment"];
        $charges = $this->getloanProductcharge($loandetails[0]['product_id']);

        if ($status == 'Closed') {
            header('Location: ' . URL . 'loans/makepaymententernunber/fullPayment?msg=closed');
            die();
        }

        ///////////////////////////////// ADDED BY STEVEN ///////////////////////////////////

        $paymentdetail = $this->amountRedistribution($account_no, $transaction_amount);

        $loan_repayment_schedule_id = $paymentdetail["shedule_id"];
        $principal_repayment = $paymentdetail["principal"];
        $interest_repayment = $paymentdetail["interest"];
        $total = $paymentdetail["total"];

        ////////////////////////////////////////////////////////////////////////////////////////

        $paymentdetails = $this->scheduledetailsdue($account_no);

        if (empty($paymentdetails)) {
            header('Location: ' . URL . 'loans/makepaymententernunber/fullPayment?msg=completed');
            die();
        }

        $loan_wallet_mapping = $this->GetGLPointers($loandetails[0]['product_id'], 2, 'Loan Repayment through Wallet Account');

        $loan_savings_mapping = $this->GetGLPointers($loandetails[0]['product_id'], 2, 'Loan Repayment Through Savings');

        $lon_rep_mapping = $this->GetGLPointers($loandetails[0]['product_id'], 2, 'Loan Repayment');

        /* if (empty($loan_wallet_mapping)) {
		header('Location: ' . URL . 'loans/makepaymententernunber/fullPayment?msg=wallet'); 
		die();
	} */

        if (empty($loan_savings_mapping)) {
            header('Location: ' . URL . 'loans/makepaymententernunber/fullPayment?msg=savings');
            die();
        }

        if (empty($lon_rep_mapping)) {
            header('Location: ' . URL . 'loans/makepaymententernunber/fullPayment?msg=cash');
            die();
        }

        $clientsavingdetails = $loandetails;
        //$chargedetails = $this->getloanapplicationcharges($account_no);
        $all_details = $this->all_m_loan_repayment_schedule_detail($account_no);

        $track_repayment_amt = 0;
        $track_repayment = $this->db->SelectData("SELECT * FROM m_loan_transaction  where account_no='" . $account_no . "' and transaction_reversed !='true' and transaction_type='Debit' ");
        foreach ($track_repayment as $key => $value):
            $track_repayment_amt = $track_repayment_amt + $value["amount"];
        endforeach;
        $outstanding_loan_balance_derive = count($all_details) * $installment - $track_repayment_amt;
        $outstanding_loan_balance = $outstanding_loan_balance_derive - $transaction_amount;

        try {
            $transaction_uniqid = uniqid();
            $clientsaving_id = $loandetails[0]['savings_account_no'];

            if ($data['transaction_type'] == 'linked_savings_act') {
                $s_amount = $transaction_amount;
                $status = $this->RepaymentThroughWallet($savings_account_number, $s_amount, $transaction_uniqid);
                if ($status != 'success') {
                    header('Location: ' . URL . 'loans/makepaymententernunber/fullPayment?msg=insuffient');
                    die();
                }
            }

            if ($data['transaction_type'] == 'savings_act') {
                $s_amount = $transaction_amount;
                $status = $this->RepaymentThroughSavings($savings_account_number, $s_amount, $transaction_uniqid);
                if ($status != 'success') {
                    header('Location: ' . URL . 'loans/makepaymententernunber/makepayment?msg=insuffient');
                    die();
                }
            }

            $dd = date('Y-m-d');
            $loan_transaction = [
                'office_id' => $office_id,
                'account_no' => $account_no,
                'transaction_date' => $dd,
                'amount' => $principal_repayment + $interest_repayment,
                'principal_portion_derived' => $principal_repayment,
                'interest_portion_derived' => $interest_repayment,
                'outstanding_loan_balance_derived' => $outstanding_loan_balance,
                'appuser_id' => $_SESSION['user_id'],
                'transaction_type' => 'Credit',
                'm_description' => 'Full Loan Repayment',
            ];

            $loan_transaction_id = $this->db->InsertData('m_loan_transaction', $loan_transaction);

            foreach ($paymentdetails as $key => $value):
                $shedule_id = $paymentdetails[$key]['id'];
                $amount = $transaction_amount;
                $updaterepaymentschedul = [
                    'completed' => 'true',
                    'principal_completed' => $paymentdetails[$key]['original_principal'],
                    'interest_completed' => $paymentdetails[$key]['original_interest'],
                    'obligations_met_on_date' => $dd,
                ];
                $this->db->UpdateData('m_loan_repayment_schedule', $updaterepaymentschedul, "`id` = '{$shedule_id}'");

                $m_loan_transaction_repayment = [
                    'loan_transaction_id' => $loan_transaction_id,
                    'loan_repayment_schedule_id' => $paymentdetails[$key]['id'],
                    'amount' => $paymentdetails[$key]['principal_amount'] + $paymentdetails[$key]['interest_amount'],
                    'principal_portion_derived' => $paymentdetails[$key]['principal_amount'],
                    'interest_portion_derived' => $paymentdetails[$key]['interest_amount'],
                ];

                $this->db->InsertData('m_loan_transaction_repayment_schedule_mapping', $m_loan_transaction_repayment);
            endforeach;

            $updateloan = [
                'loan_status' => 'Closed',
            ];
            $this->db->UpdateData('m_loan', $updateloan, "`account_no` = '{$account_no}'");

            $transaction_uniqid = uniqid();

            $deposit_transaction_id = $loan_transaction_id;
            $deposit_transaction_uniqid = $deposit_transaction_id . "" . $transaction_uniqid;

            $new_data['transaction_id'] = $deposit_transaction_uniqid;
            $this->db->UpdateData('m_loan_transaction', $new_data, "`id` = '{$loan_transaction_id}'");

            $prodType = 2;
            if ($data['transaction_type'] == 'linked_savings_act') {
                $mapping = $this->GetGLPointers($loandetails[0]['product_id'], $prodType, 'Loan Repayment Through Wallet Account');
            } elseif ($data['transaction_type'] == 'savings_act') {
                $mapping = $this->GetGLPointers($loandetails[0]['product_id'], $prodType, 'Loan Repayment Through Savings');
            } else {
                $transactionType = 'Loan Repayment';
                $mapping = $this->GetGLPointers($loandetails[0]['product_id'], $prodType, $transactionType);

                $tran_id = $this->getTransactionID($transactionType);
                $transaction_charges = $this->getTransactionCharges($tran_id);
                $exemptions = $this->getMemberChargeExemptions($loandetails[0]['member_id']);

                if (!empty($transaction_charges)) {
                    foreach ($transaction_charges as $key => $value) {
                        if (is_null($exemptions) || !in_array($value, $exemptions)) {
                            $mapping_charges = $this->GetGLChargePointers($value['id'], $prodType, $transactionType, $tran_id);
                            if (!empty($mapping_charges)) {
                                $transaction_amount += $value['amount'];

                                $debt_id = $mapping_charges[0]["debit_account"];
                                $credit_id = $mapping_charges[0]["credit_account"];

                                $sideA = $this->getAccountSide($debt_id);
                                $sideB = $this->getAccountSide($credit_id);

                                $description = ucfirst($value['name']) . " Charge";

                                $uniq_id = $_SESSION['user_id'] . uniqid();
                                $transaction_id = "L" . $uniq_id;

                                $amount = $value['amount'];

                                $this->makeJournalEntry($debt_id, $_SESSION['office'], $_SESSION['user_id'], $uniq_id, $transaction_id, $amount, 'DR', $sideA, $description); //DR
                                $this->makeJournalEntry($credit_id, $_SESSION['office'], $_SESSION['user_id'], $uniq_id, $transaction_id, $amount, 'CR', $sideB, $description); //CR

                                $postCharges = [
                                    'account_no' => $account_no,
                                    'charge_id' => $value['id'],
                                    'charge_name' => $value['name'],
                                    'amount' => $value['amount'],
                                ];

                                $this->db->InsertData('m_loan_charge', $postCharges);

                                $postTransCharges = [
                                    'transaction_id' => $transaction_id,
                                    'charge_id' => $value['id'],
                                    'trans_amount' => $amount,
                                    'date' => date("Y-m-d H:i:s"),
                                ];

                                $this->db->InsertData('m_charge_transaction', $postTransCharges);
                            }
                        } else {
                            //echo "Member " . $loandetails[0]['member_id'] . " Exepted From " . ucwords($value['name']) . "</br>";
                        }
                    }
                }
            }

            if (!empty($mapping)) {
                $transaction_id = "L" . $deposit_transaction_uniqid;
                $debt_id_principal = $mapping[0]["debit_account"];
                $credit_id_principal = $mapping[0]["credit_account"];
                $sideA = $this->getAccountSide($debt_id_principal);
                $sideB = $this->getAccountSide($credit_id_principal);

                $this->makeJournalEntry($debt_id_principal, $office_id, $_SESSION['user_id'], $deposit_transaction_uniqid, $transaction_id, $transaction_amount, 'DR', $sideA); //DR
                $this->makeJournalEntry($credit_id_principal, $office_id, $_SESSION['user_id'], $deposit_transaction_uniqid, $transaction_id, $transaction_amount, 'CR', $sideB); //CR
                $this->db->commit();
            }
        } catch (Exception $e) {
            $this->db->rollBack();
            $error = $e->getMessage();
            header('Location: ' . URL . 'loans/makepaymententernunber/fullPayment?msg=failed');
            die();
        }

        $status = 'payment successful';
        header('Location: ' . URL . 'loans/makepaymententernunber/fullPayment?msg=success');
        die();
    }

    function reverseloanrepayment()
    {
        $trans = 'reversed';
        $data = $_POST;

        $tansno = $data['tnumber'];
        $tansamount = str_replace(',', '', $data['tamount']);
        $accno = $data['account_no'];

        $m_loan = $this->db->SelectData("SELECT * FROM m_loan WHERE account_no='" . $accno . "'  ");
        $m_loan_trn = $this->db->SelectData("SELECT * FROM m_loan_transaction WHERE id='" . $tansno . "'  ");
        $repayment_schedule_mapping = $this->db->SelectData("SELECT * FROM m_loan_transaction_repayment_schedule_mapping WHERE loan_transaction_id='" . $tansno . "'  ");
        $account_no = $m_loan[0]['account_no'];
        $loan_repayment_schedule_id = $repayment_schedule_mapping[0]['loan_repayment_schedule_id'];

        $loandetails = $this->loandetails($account_no);

        $outstanding_loan_balance_derive = $m_loan_trn[0]['outstanding_loan_balance_derived'];
        $outstanding_loan_balance_derive = $outstanding_loan_balance_derive + $tansamount;

        $loan_transaction = [
            'outstanding_loan_balance_derived' => str_replace(',', '', number_format($outstanding_loan_balance_derive)),
            'reversed_by' => $_SESSION['user_id'],
            'reversed_amount' => $tansamount,
            'transaction_reversed' => 'Yes',
        ];

        $this->db->UpdateData('m_loan_transaction', $loan_transaction, "`id` = '{$tansno}'");
        foreach ($repayment_schedule_mapping as $key => $values):
            $loan_repayment_schedule_id = $repayment_schedule_mapping[$key]['loan_repayment_schedule_id'];
            $schedule_mappin_id = $repayment_schedule_mapping[$key]['id'];

            $m_loan_transaction_repayment = [
                'status' => 'reversed',
            ];

            $this->db->UpdateData('m_loan_transaction_repayment_schedule_mapping', $m_loan_transaction_repayment, "`id` = '{$schedule_mappin_id}'");
            $repayment_schedule = $this->db->SelectData("SELECT * FROM m_loan_repayment_schedule WHERE id='" . $loan_repayment_schedule_id . "'  ");
            $originalPrincipalCompleted = $repayment_schedule[0]['principal_completed'];
            $originalInterestCompleted = $repayment_schedule[0]['interest_completed'];
            $newPrincipalCompleted = $originalPrincipalCompleted - $repayment_schedule_mapping[$key]['principal_portion_derived'];
            $newInterestCompleted = $originalInterestCompleted - $repayment_schedule_mapping[$key]['interest_portion_derived'];
            if ($newPrincipalCompleted < 0) {
                $newPrincipalCompleted = 0;
            }
            if ($newInterestCompleted < 0) {
                $newInterestCompleted = 0;
            }
            $updaterepaymentschedul = [
                'principal_completed' => $newPrincipalCompleted,
                'interest_completed' => $newInterestCompleted,
                'completed' => 'reversed',
            ];

            $this->db->UpdateData('m_loan_repayment_schedule', $updaterepaymentschedul, "`id` = '{$loan_repayment_schedule_id}'");
        endforeach;
        header('Location: ' . URL . 'loans/reverseloanstransaction/' . $account_no . '?trans=' . $trans . '');
    }

    function getloanstransaction($acc, $transno, $tdate)
    {
        $m_loan = $this->db->SelectData("SELECT * FROM m_loan WHERE account_no='" . $acc . "'  ");
        $loan_id = $m_loan[0]['loan_id'];

        if ($tdate == null) {
            $result = $this->db->SelectData("SELECT * FROM m_loan_transaction WHERE transaction_id='" . $transno . "' ");
        } else {
            //$trans_date= date('Y-m-d',strtotime(str_replace('-','/',$tdate)));
            $trans_date = date('Y-m-d', strtotime($tdate));
            $result = $this->db->SelectData("SELECT * FROM m_loan_transaction WHERE loan_id='" . $loan_id . "' and transaction_id='" . $transno . "' and date(transaction_date)='" . $trans_date . "'  ");
        }

        $rset = [];
        if (count($result) > 0) {
            array_push($rset, ['amount' => number_format($result[0]['amount'], 2, ".", ",")]);
            echo json_encode($rset);
            die();
        }
    }

    function upDateLoanSavingsAccount()
    {
        $data = $_POST;
        $account_no = $data['account_no'];

        $postData = [
            'savings_account_no' => $data['savings_account_number'],
        ];

        $this->db->UpdateData('m_loan', $postData, "`account_no` = '{$account_no}'");

        header('Location: ' . URL . 'loans/modifyLoanAccountInfo/' . $account_no . '?msg=success');
    }

    function updateDisbursement()
    {
        $data = $_POST;
        $account_no = $data['account_no'];
        $disbursement_date = date('Y-m-d', strtotime($data['disbursement_date']));

        $postData = [
            'disbursedon_date' => $disbursement_date,
        ];
        $status = 'updated';

        $this->db->UpdateData('m_loan', $postData, "`account_no` = '{$account_no}'");
        header('Location: ' . URL . 'loans/nextdisbursement/' . $account_no . '?msg= ' . $status . '');
    }

    function scheduledetailspaid($id)
    {
        $principal_completed = 0;

        $interest_completed = null;

        //and interest_completed  != '" . $interest_completed . "'
        $results = $this->db->SelectData(
            "SELECT * FROM m_loan_repayment_schedule WHERE account_no='" . $id . "'  and (principal_completed  > '" . $principal_completed . "' or interest_completed  != '" . $interest_completed . "')  order by id "
        );

        return $results;
    }

    function loantransaction($id)
    {
        $results = $this->db->SelectData("SELECT * FROM m_loan_transaction WHERE account_no='" . $id . "'");
        return $results;
    }

    function scheduledetails($id)
    {
        $results = $this->db->SelectData("SELECT * FROM m_loan_repayment_schedule WHERE account_no='" . $id . "'");
        if ($results) {
            return $results;
        } else {
            return false;
        }
    }

    function getLoanStatement($data)
    {
        $date = date('Y-m-d');
        $first_day = date('Y-m-01');

        $account_no = $data['account_no'];
        $option = $data['loanoption'];
        $start = date('Y-m-d', strtotime($data['date1']));
        $end = date('Y-m-d', strtotime($data['date2']));

        if ($option == 'current month') {
            $results = $this->db->SelectData("SELECT * FROM m_loan_transaction WHERE  account_no='" . $account_no . "' AND transaction_date BETWEEN '" . $first_day . "' AND '" . $date . "' ");
        } elseif ($option == 'specified period') {
            $results = $this->db->SelectData("SELECT * FROM m_loan_transaction WHERE  account_no='" . $account_no . "' AND transaction_date BETWEEN '" . $start . "' AND '" . $end . "' ");
        }
        return $results;
    }

    function RepaymentThroughWallet($acc, $amount, $transaction_id)
    {
        $prodType = 2;
        $transactionType = 'Loan Repayment through Wallet Account';
        $tran_id = $this->getTransactionID($transactionType);
        $transaction_charges = $this->getTransactionCharges($tran_id);
        $total_charge_amount = 0;

        if (!empty($transaction_charges)) {
            foreach ($transaction_charges as $key => $value) {
                $mapping_charges = $this->GetGLChargePointers($value['id'], $prodType, $transactionType, $tran_id);
                if (!empty($mapping_charges)) {
                    $total_charge_amount += $value['amount'];
                }
            }
        }

        $result = $this->db->selectData("SELECT * FROM sm_mobile_wallet WHERE wallet_account_number='" . $acc . "' ");

        $balance = $result[0]['wallet_balance'];

        $new_balance = $balance - ($amount + $total_charge_amount);
        $office_id = $_SESSION['office'];

        //$chargedetails = $this->getsavingsProductcharge_application();

        $exemptions = $this->getMemberChargeExemptions($result[0]['member_id']);
        if ($new_balance >= 0) {
            if (!empty($transaction_charges)) {
                foreach ($transaction_charges as $key => $value) {
                    if (is_null($exemptions) || !in_array($value, $exemptions)) {
                        $mapping_charges = $this->GetGLChargePointers($value['id'], $prodType, $transactionType, $tran_id);
                        if (!empty($mapping_charges)) {
                            $debt_id = $mapping_charges[0]["debit_account"];
                            $credit_id = $mapping_charges[0]["credit_account"];

                            $sideA = $this->getAccountSide($debt_id);
                            $sideB = $this->getAccountSide($credit_id);

                            $description = ucfirst($value['name']) . " Charge";

                            $amount = $value['amount'];
                            $balance -= $amount;

                            $this->makeJournalEntry($debt_id, $_SESSION['office'], $_SESSION['user_id'], $uniq_id, $transaction_id, $amount, 'DR', $sideA, $description); //DR
                            $this->makeJournalEntry($credit_id, $_SESSION['office'], $_SESSION['user_id'], $uniq_id, $transaction_id, $amount, 'CR', $sideB, $description); //CR

                            $postCharges = [
                                'account_no' => $acc,
                                'charge_id' => $value['id'],
                                'charge_name' => $value['name'],
                                'amount' => $value['amount'],
                            ];

                            $this->db->InsertData('m_loan_charge', $postCharges);

                            $postTransCharges = [
                                'transaction_id' => $transaction_id,
                                'charge_id' => $value['id'],
                                'trans_amount' => $amount,
                                'date' => date("Y-m-d H:i:s"),
                            ];

                            $this->db->InsertData('m_charge_transaction', $postTransCharges);

                            $data['amount_in_words'] = $this->convertNumber($amount);
                            $data['wallet_balance'] = $balance;
                            $data['amount'] = $amount;
                            $data['transaction_type'] = 'Loan';
                            $data['description'] = 'To : Secure Loan';
                            $data['wallet_account_number'] = $acc;
                            $data['transaction_id'] = $transaction_id;
                            $this->logWalletTransaction($data);
                        }
                    } else {
                        //echo "Member " . $result[0]['member_id'] . " Exepted From " . ucwords($value['name']) . "</br>";
                    }
                }
            }
            $data['amount_in_words'] = $this->convertNumber($amount);
            $data['wallet_balance'] = $new_balance;
            $data['amount'] = $amount;
            $data['transaction_type'] = 'Loan';
            $data['description'] = 'To : Secure Loan';
            $data['wallet_account_number'] = $acc;
            $data['transaction_id'] = $transaction_id;
            $this->logWalletTransaction($data);

            return "success";
        } else {
            return 'insuffient funds';
        }
    }

    function RepaymentThroughSavings($acc, $amount, $transaction_id)
    {
        $prodType = 2;
        $transactionType = 'Loan Repayment Through Savings';
        $tran_id = $this->getTransactionID($transactionType);
        $transaction_charges = $this->getTransactionCharges($tran_id);
        $total_charge_amount = 0;

        if (!empty($transaction_charges)) {
            foreach ($transaction_charges as $key => $value) {
                $mapping_charges = $this->GetGLChargePointers($value['id'], $prodType, $transactionType, $tran_id);
                if (!empty($mapping_charges)) {
                    $total_charge_amount += $value['amount'];
                }
            }
        }

        $office_id = $_SESSION['office'];
        $result = $this->db->selectData("SELECT * FROM m_savings_account WHERE account_no='" . $acc . "' AND office_id = '" . $office_id . "'");

        $balance = $result[0]['running_balance'];
        $new_balance = $balance - ($amount + $total_charge_amount);

        $uniq_id = $_SESSION['user_id'] . uniqid();
        //$transaction_id =  "S".$uniq_id;
        //$chargedetails = $this->getsavingsProductcharge_application();

        $exemptions = $this->getMemberChargeExemptions($result[0]['member_id']);
        if ($new_balance >= 0) {
            if (!empty($transaction_charges)) {
                foreach ($transaction_charges as $key => $value) {
                    if (is_null($exemptions) || !in_array($value, $exemptions)) {
                        $mapping_charges = $this->GetGLChargePointers($value['id'], $prodType, $transactionType, $tran_id);
                        if (!empty($mapping_charges)) {
                            $debt_id = $mapping_charges[0]["debit_account"];
                            $credit_id = $mapping_charges[0]["credit_account"];

                            $sideA = $this->getAccountSide($debt_id);
                            $sideB = $this->getAccountSide($credit_id);

                            $description = ucfirst($value['name']) . " Charge";

                            $amount = $value['amount'];
                            $balance -= $amount;

                            $this->makeJournalEntry($debt_id, $_SESSION['office'], $_SESSION['user_id'], $uniq_id, $transaction_id, $amount, 'DR', $sideA, $description); //DR
                            $this->makeJournalEntry($credit_id, $_SESSION['office'], $_SESSION['user_id'], $uniq_id, $transaction_id, $amount, 'CR', $sideB, $description); //CR

                            $postCharges = [
                                'account_no' => $acc,
                                'charge_id' => $value['id'],
                                'charge_name' => $value['name'],
                                'amount' => $value['amount'],
                            ];

                            $this->db->InsertData('m_loan_charge', $postCharges);

                            $postTransCharges = [
                                'transaction_id' => $transaction_id,
                                'charge_id' => $value['id'],
                                'trans_amount' => $amount,
                                'date' => date("Y-m-d H:i:s"),
                            ];

                            $this->db->InsertData('m_charge_transaction', $postTransCharges);

                            $data['savings_account_no'] = $acc;
                            $data['transaction_type'] = 'Transfer';
                            $data['transaction_id'] = $transaction_id;
                            $data['payment_detail_id'] = 'CASH';
                            $data['transaction_date'] = date("Y-m-d H:i:s");
                            $data['running_balance'] = $balance;
                            $data['amount'] = $amount;
                            $data['amount_in_words'] = $this->convertNumber($amount);
                            $data['depositor_name'] = $description;
                            $data['telephone_no'] = 'To : Secure Loan';
                            $data['branch'] = $_SESSION['office'];
                            $data['user_id'] = $_SESSION['user_id'];

                            $this->db->InsertData('m_savings_account_transaction', $data);
                        }
                    } else {
                        //echo "Member " . $result[0]['member_id'] . " Exepted From " . ucwords($value['name']) . "</br>";
                    }
                }
            }

            $data['savings_account_no'] = $acc;
            $data['transaction_type'] = 'Transfer';
            $data['transaction_id'] = $transaction_id;
            $data['payment_detail_id'] = 'CASH';
            $data['transaction_date'] = date("Y-m-d H:i:s");
            $data['amount_in_words'] = $this->convertNumber($amount);
            $data['running_balance'] = $new_balance;
            $data['amount'] = $amount;
            $data['depositor_name'] = 'System';
            $data['telephone_no'] = 'To : Secure Loan';
            $data['branch'] = $office_id;
            $data['user_id'] = $_SESSION['user_id'];

            $id = $result[0]['id'];
            $postData['running_balance'] = $new_balance;

            $this->db->InsertData('m_savings_account_transaction', $data);
            $this->db->UpdateData('m_savings_account', $postData, "id = {$id}");

            return "success";
        } else {
            return 'insuffient funds';
        }
    }

    function logSavingsTransaction($data)
    {
        $this->db->InsertData('m_savings_account_transaction', $data);
        $this->db->UpdateData('m_savings_account', $postData, "wallet_account_number = {$walletaccount}");
    }
    function withdrawaccodsunt($acc, $amount, $name)
    {
        $result = $this->db->selectData("SELECT * FROM m_savings_account WHERE account_no='" . $acc . "' ");
        $product = $this->db->selectData("SELECT * FROM m_savings_product WHERE id='" . $result[0]['product_id'] . "'");

        $balance = $result[0]['running_balance'];
        $availablewithdraw = $result[0]['total_withdrawals'];

        $new_total_withdraws = $availablewithdraw + $amount;
        $new_balance = $balance - $amount;
        $office_id = $_SESSION['office'];

        //$chargedetails = $this->getsavingsProductcharge_application();

        $actualbalance = $new_balance;
        $min_balance = $product[0]['min_required_balance'];

        if ($actualbalance >= $min_balance) {
            $transaction_uniqid = uniqid();

            $withdrawstatus = [
                'total_withdrawals' => $new_total_withdraws,
                'running_balance' => $new_balance,
            ];

            $this->db->UpdateData('m_savings_account', $withdrawstatus, "`account_no` = '{$acc}'");

            $transaction_postData = [
                'savings_account_no' => $acc,
                'amount' => $amount,
                'transaction_type' => 'Withdraw',
                'transaction_id' => 'SW' . $transaction_uniqid,
                'depositor_name' => $name,
                'running_balance' => $new_balance,
                'branch' => $_SESSION['office'],
                'user_id' => $_SESSION['user_id'],
            ];

            $deposit_transaction_id = $this->db->InsertData('m_savings_account_transaction', $transaction_postData);

            return 'success';
        } else {
            return 'insuffient funds';
        }
    }

    function ResheduleLoan()
    {
    }
    function CancelApprovedLoan()
    {
    }
    function searchLoan()
    {
    }

    function customersupportshedule($id, $p, $np, $d1)
    {
        $dt = $d1;
        $date = date("Y-m-d", strtotime($dt));
        $principle = 0;

        $product_loan_details = $this->db->SelectData("SELECT * FROM m_product_loan  where id='" . $id . "' order by id ");

        $principal = $p;
        $number_of_repayments = $np;
        $annual_interest_percent = $product_loan_details[0]['nominal_interest_rate_per_period'];
        $days_in_year = $product_loan_details[0]['days_in_year'];
        $installment_option = $product_loan_details[0]['installment_option'];
        $interest_method = $product_loan_details[0]['interest_method'];
        $duration = $product_loan_details[0]['duration'];
        $duration_value = $product_loan_details[0]['min_duration_value'];
        $grace_period = $product_loan_details[0]['grace_period'];
        $grace_period_value = $product_loan_details[0]['grace_period_value'];

        $loan_details = $product_loan_details;

        $date = date("Y-m-d", strtotime($dt));
        if ($installment_option == "One Installment") {
            // Equal installment declining balance
        } else {
            if ($interest_method == "Declining Balance") {
                $no_date = 1;

                $year_term = 0;
                $down_percent = 0;
                $this_year_interest_paid = 0;
                $this_year_principal_paid = 0;
                $year_term = 1;
                $remaining_bal = 0;
                $loanTermFrequencyType = $duration;

                if ($loanTermFrequencyType == "days") {
                    $period_cal = $duration_value / $loan_details[0]['days_in_year'];
                    $period_modulus = $duration_value % $loan_details[0]['days_in_year'];
                    $number_of_month = $loan_details[0]['days_in_year'] / 12;
                    $period = 12 / $number_of_repayments / 12;
                    //(12/$number_of_repayments)/ 12  $period = (12/$number_of_repayments )/ 12;
                    $change_months = ($duration_value * 12) / $loan_details[0]['days_in_year'];

                    if ($period_cal == 1) {
                        $loop = $number_of_repayments;
                    } else {
                        if ($duration_value > $loan_details[0]['days_in_year']) {
                            $loop = $number_of_repayments * $period_cal;
                        } else {
                            $loop = $number_of_repayments;
                        }
                    }
                } elseif ($loanTermFrequencyType == "weeks") {
                    $period = $number_of_repayments / number_format($loan_details[0]['days_in_year'] / 7);
                    $period_cal = $duration_value / number_format($loan_details[0]['days_in_year'] / 7);
                    $period_modulus = $duration_value % $loan_details[0]['days_in_year'];
                    $change_months = ($duration_value * 7 * 12) / $loan_details[0]['days_in_year'];
                    if ($period_cal == 1) {
                        $loop = $number_of_repayments;
                        //$loop = $number_of_repayments * (12 *ceil($period_cal)/12) ;
                        $period = 12 / $number_of_repayments / 12;
                    } else {
                        if ($duration_value > number_format($loan_details[0]['days_in_year'] / 7)) {
                            $loop = $number_of_repayments * (($duration_value * $period_cal) / number_format($loan_details[0]['days_in_year'] / 7));
                            $period = 12 / $number_of_repayments / 12;
                        } else {
                            $loop = $number_of_repayments;
                            $period = 12 / $number_of_repayments / 12;
                        }
                    }
                } elseif ($loanTermFrequencyType == "months") {
                    //$monthly_interest_rate = 5/100*(12/7)/12;

                    $period = 12 / $number_of_repayments / 12;
                    $period_cal = $duration_value / 12;
                    $period_modulus = $duration_value % $loan_details[0]['days_in_year'];

                    if ($period_cal == 1) {
                        $loop = $number_of_repayments;
                        $period = 12 / $number_of_repayments / 12;
                    } else {
                        if ($duration_value > 12) {
                            $loop = $number_of_repayments * ((12 * $period_cal) / 12);
                            $period = 12 / $number_of_repayments / 12;
                        } else {
                            $loop = $number_of_repayments;
                            $period = 12 / $number_of_repayments / 12;
                        }
                    }
                } else {
                    $loop = $number_of_repayments * $duration_value;

                    $period = 12 / $number_of_repayments / 12;
                }

                $step = 1;
                $month_term = $number_of_repayments;
                $annual_interest_rate = $annual_interest_percent / 100;
                $monthly_interest_rate = $annual_interest_rate * $period;
                $loan_duration_value = $duration_value;
                $no_duration = $loan_duration_value / $number_of_repayments;
                $current_month = 1;
                $current_no = 0;
                $current_year = 1;
                $power = -$loop;
                $denom = pow(1 + $monthly_interest_rate, $power);
                $monthly_payment = $principal * ($monthly_interest_rate / (1 - $denom));
                $monthly_payment_installment = number_format($monthly_payment, 0, ".", "");
                $fullPay = $monthly_payment_installment * $number_of_repayments;
                $total_principle = 0;
                $bal = 0;
                $total_interest = 0;
                $total_charge = 0;
                $account_no = null;

                $date = $this->loans_calculations->repaymentdate($grace_period, $date, $grace_period_value);

                $month_term = ceil($loop);

                while ($current_month <= $month_term) {
                    $interest_paid = $principal * $monthly_interest_rate;
                    $principal_paid = $monthly_payment - $interest_paid;
                    $remaining_balance = $principal - $principal_paid;

                    $from = $date;

                    if ($loanTermFrequencyType == "days") {
                        $date = date("Y-m-d", strtotime($date . " +" . number_format($no_duration) . " day"));
                    } elseif ($loanTermFrequencyType == "weeks") {
                        if (is_float($no_duration)) {
                            $repay_every = number_format(7 * $no_duration);
                            $date = date("Y-m-d", strtotime($date . " +" . $repay_every . " day"));
                        } else {
                            $date = date("Y-m-d", strtotime($date . " +" . number_format($no_duration) . " week"));
                        }
                    } elseif ($loanTermFrequencyType == "months") {
                        if (is_float($no_duration)) {
                            $repay_every = number_format(($days_in_year / 12) * $no_duration);
                            $date = date("Y-m-d", strtotime($date . " +" . $repay_every . " day"));
                        } else {
                            $date = date("Y-m-d", strtotime($date . " +" . number_format($no_duration) . " month"));
                        }
                    } elseif ($loanTermFrequencyType == "years") {
                        if (is_float($no_duration)) {
                            $repay_every = number_format($days_in_year * $no_duration);
                            $date = date("Y-m-d", strtotime($date . " +" . $repay_every . " day"));
                        } else {
                            $date = date("Y-m-d", strtotime($date . " +" . number_format($no_duration * 12) . " month"));
                        }
                    }

                    $date = date("Y-m-d", strtotime($date));
                    $to = $date;
                    $this_year_interest_paid = $this_year_interest_paid + $interest_paid;
                    $this_year_principal_paid = $this_year_principal_paid + $principal_paid;
                    $princial_per = number_format($principal_paid, 0, ".", "");
                    $interest_per = number_format($interest_paid, 0, ".", "");
                    $total_principle = $total_principle + $princial_per;
                    $total_interest = $total_interest + $interest_per;
                    $total_charge = $total_charge + 0;
                    $bal = $fullPay - ($total_principle + $total_interest);

                    $rset[$current_no]['account_no'] = $account_no;
                    $rset[$current_no]['installment'] = $monthly_payment_installment;
                    $rset[$current_no]['fromdate'] = $from;
                    $rset[$current_no]['duedate'] = $to;
                    $rset[$current_no]['principal_amount'] = $princial_per;
                    $rset[$current_no]['interest_amount'] = $interest_per;
                    $rset[$current_no]['fee_charges_amount'] = 0;
                    $rset[$current_no]['principal_completed'] = $total_principle;
                    $rset[$current_no]['interest_completed'] = $total_interest;
                    $rset[$current_no]['fee_charges_completed'] = $total_charge;
                    $rset[$current_no]['createdby_id'] = '';
                    $rset[$current_no]['remaining_balance'] = $bal;
                    $rset[$current_no]['created_date'] = date("Y-m-d");

                    $current_month % 12 ? ($show_legend = false) : ($show_legend = true);

                    if ($show_legend) {
                        $current_year++;
                        $this_year_interest_paid = 0;
                        $this_year_principal_paid = 0;
                        if ($current_month + 6 < $month_term) {
                            // echo $legend;
                        }
                    }

                    $principal = $remaining_balance;
                    $current_month++;
                    $current_no++;
                }

                print_r(json_encode($rset));
                die();
            } else {
                $loanTermFrequencyType = $duration;

                if ($loanTermFrequencyType == "days") {
                    $period_cal = $duration_value / $loan_details[0]['days_in_year'];
                    $period_modulus = $duration_value % $loan_details[0]['days_in_year'];
                    $number_of_month = $loan_details[0]['days_in_year'] / 12;
                    $period = $duration_value / $loan_details[0]['days_in_year'];

                    $change_months = ($duration_value * 12) / $loan_details[0]['days_in_year'];

                    if ($period_cal == 1) {
                        $loop = $number_of_repayments;
                    } else {
                        if ($duration_value > $loan_details[0]['days_in_year']) {
                            $loop = $number_of_repayments * $period_cal;
                        } else {
                            $loop = $number_of_repayments;
                        }
                    }
                } elseif ($loanTermFrequencyType == "weeks") {
                    $period = $duration_value / number_format($loan_details[0]['days_in_year'] / 7);
                    $period_cal = $duration_value / number_format($loan_details[0]['days_in_year'] / 7);
                    $period_modulus = $duration_value % $loan_details[0]['days_in_year'];
                    $change_months = ($duration_value * 7 * 12) / $loan_details[0]['days_in_year'];
                    if ($period_cal == 1) {
                        $loop = $number_of_repayments;
                    } else {
                        if ($duration_value > number_format($loan_details[0]['days_in_year'] / 7)) {
                            $loop = $number_of_repayments * (($duration_value * $period_cal) / number_format($loan_details[0]['days_in_year'] / 7));
                        } else {
                            $loop = $number_of_repayments;
                        }
                    }
                } elseif ($loanTermFrequencyType == "months") {
                    //$monthly_interest_rate = 5/100*(12/7)/12;

                    $period = $duration_value / 12;
                    $period_cal = $duration_value / 12;
                    $period_modulus = $duration_value % $loan_details[0]['days_in_year'];
                    if ($period_cal == 1) {
                        $loop = $number_of_repayments;
                    } else {
                        if ($duration_value > 12) {
                            $loop = $number_of_repayments * ((12 * $period_cal) / 12);
                        } else {
                            $loop = $number_of_repayments;
                        }
                    }
                } else {
                    $loop = $number_of_repayments * $duration_value;
                    $period = $duration_value;
                }

                $no_duration = $duration_value / $number_of_repayments;
                $interest = $principal * $period * ($annual_interest_percent / 100);
                $Principal_amount = $interest + $principal;
                $monthly_pay = $Principal_amount / $loop;
                $monthly_interest = $interest / $loop;
                $monthly_Principal = $principal / $loop;
                $daycount = 0;

                $date = $this->loans_calculations->repaymentdate($grace_period, $date, $grace_period_value);

                $fullPay = $Principal_amount;
                $total_principle = $monthly_Principal;
                $total_interest = $monthly_interest;
                $bal = $fullPay - ($total_principle + $total_interest);

                $total_charge = 0;
                $number_of_repayments = ceil($loop);
                for ($i = 0; $i < $number_of_repayments; $i++) {
                    $from = $date;
                    if ($loanTermFrequencyType == "days") {
                        $date = date("Y-m-d", strtotime($date . " +" . number_format($no_duration) . " day"));
                    } elseif ($loanTermFrequencyType == "weeks") {
                        if (is_float($no_duration)) {
                            $repay_every = number_format(7 * $no_duration);
                            $date = date("Y-m-d", strtotime($date . " +" . $repay_every . " day"));
                        } else {
                            $date = date("Y-m-d", strtotime($date . " +" . number_format($no_duration) . " week"));
                        }
                    } elseif ($loanTermFrequencyType == "months") {
                        if (is_float($no_duration)) {
                            $repay_every = number_format(($days_in_year / 12) * $no_duration);
                            $date = date("Y-m-d", strtotime($date . " +" . $repay_every . " day"));
                        } else {
                            $date = date("Y-m-d", strtotime($date . " +" . number_format($no_duration) . " month"));
                        }
                    } elseif ($loanTermFrequencyType == "years") {
                        if (is_float($no_duration)) {
                            $repay_every = number_format($days_in_year * $no_duration);
                            $date = date("Y-m-d", strtotime($date . " +" . $repay_every . " day"));
                        } else {
                            $date = date("Y-m-d", strtotime($date . " +" . number_format($no_duration * 12) . " month"));
                        }
                    }

                    $date = date("Y-m-d", strtotime($date));
                    $to = $date;

                    $rset[$i]['account_no'] = null;
                    $rset[$i]['installment'] = number_format($monthly_pay, 0, ".", "");
                    $rset[$i]['fromdate'] = $from;
                    $rset[$i]['duedate'] = $to;
                    $rset[$i]['principal_amount'] = number_format($monthly_Principal, 0, ".", "");
                    $rset[$i]['interest_amount'] = number_format($monthly_interest, 0, ".", "");
                    $rset[$i]['fee_charges_amount'] = 0;
                    $rset[$i]['principal_completed'] = number_format($total_principle, 0, ".", "");
                    $rset[$i]['interest_completed'] = number_format($total_interest, 0, ".", "");
                    $rset[$i]['fee_charges_completed'] = number_format($total_charge, 0, ".", "");
                    $rset[$i]['remaining_balance'] = number_format($bal, 0, ".", "");

                    $total_principle = $total_principle + $monthly_Principal;

                    $total_interest = $total_interest + $monthly_interest;
                    $bal = $fullPay - ($total_principle + $total_interest);
                }

                print_r(json_encode($rset));
                die();
            }
        }
    }
}
