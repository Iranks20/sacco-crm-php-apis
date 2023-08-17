<?php error_reporting(0);
    if(isset($_GET["msg"])){
        $acc=$_GET['msg'];
        if ($res = 'success') { ?>
        <script type="text/javascript">
            $(document).ready(function (){
                swal( "", "Insurance Claim Made Successful", 'success');
            });

        </script>
        <?php
        }
    }
?>
<div class="mainapp-content-wrapper">
    <div class="full-wrapper main-titles">
       <div class="nt-grid">
        <ol class="cd-breadcrumb triangle">
            <li><a href="<?php echo URL;?>">Admin</a></li>
            <li class="current"><em>Insurance Claims</em></li>
        </ol>                
    </div><!-- end .nt-grid --> 
</div><!-- full-wrapper -->
<div class="full-wrapper">
   <div class="grid-wrapper">
       <div class="nt-grid">
        <div class="holder-widget">
            <div class="wigdet-spacer">
              <?php require_once('views/insurance_menu.php'); ?>
          </div><!-- widget-spacer -->
          <div class="tables-profile">
            <div class="nt-row">

              <div class="col-sm-12">
                 <div class="panel panel-default panel-hovered panel-stacked mb30">
                    <div class="panel-body">
                       <!-- inner row -->
                       <div class="row">
                        <div class="col-md-12">
                            <div class="panel-ctrls">
                            </div>
                            <!-- data table -->
                            <div class="resp-table">
                                <table  id="example" class="xui-table compact-table">
                                    <thead>
                                        <tr>
                                            <th>Member No</th>
                                            <th>Member Name</th>
                                            <th>Insurance Category</th>
                                            <th>Insurance Product</th>
                                            <th>Account No</th>
                                            <th>Claim Date</th>
                                            <th>Approval Date</th>
                                            <th>Claim Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                      <?php
                                      if (!empty($this->claims)){ 
                                          foreach ($this->claims as $key => $value): ?>
                                            <tr>
                                                <td><?php echo $value["member_id"] ; ?></td>
                                                <td><?php if(!empty($value["company_name"])){echo $value["company_name"];}else{ echo $value["firstname"]." ".$value["middlename"]." ".$value["lastname"]; } ?></td>
                                                <td><a href="<?php echo URL; ?>insurance/insuranceaccountdetails/<?php  echo $value['account_no']; ?>/<?php  echo $value['member_id']; ?>"><?php echo $value["account_no"]; ?></a></td>
                                                <td><?php echo $value["opened_on"]; ?></td>
                                                <td><?php echo $value["status"]; ?></td>
                                                <td>
                                                 <a href="<?php echo URL;?>insurance/insuranceaccountdetails/<?php echo $value['account_no'];?>" >View Details
                                                 </a>
                                             </td>                                                   
                                         </tr>
                                     <?php endforeach; } ?> 									
                                 </tbody>
                             </table>
                         </div> 

                     </div>

                 </div> <!-- #end inner row -->
             </div> <!-- #end panel-body -->
         </div> <!-- #end panel -->
     </div>
 </div>
 <!-- #end row -->


</div>
</div><!-- .holder-widget -->                
</div><!-- end .nt-grid -->
</div><!-- .grid-wrapper -->  
</div><!-- full-wrapper -->              

</div><!-- .mainapp-content-wrapper -->

