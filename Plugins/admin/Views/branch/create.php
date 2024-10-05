<div class="content-wraper-area">
    <!-- Basic Form area Start -->
    <div class="container-fluid">
        <div class="row g-4">
            <div class="col-12">
                <div class="card">
                    [array breadcrumb_list]
                    [array.new]
                    [item href][get site_url/]/admin/Branch[/item]
                    [item text]Branch[/item]
                    [/array]
                    [array.new]
                    [item active]active[/item]
                    [item text]Create[/item]
                    [/array]
                    [/array]
                    [ll.pc.breadcrumb "{breadcrumb_list}" title="Create" /]
                </div>
            </div>

            <div class="col-12">
                <div class="row">
                    <div class="col"></div>
                    <div class="col-xl-6">
                        <div class="card card-h-100">
                            <div class="card-body">
                                <div class="card-title">
                                    <h4 class="card-title">Create New Branch</h4>
                                </div>
                                    [form.branch_create post action="{site_url}/admin/branches/branch-create"]
                                    <div class="error-container mb-3"></div>
                                    <div class="message-container mb-3"></div>
                                    <div class="row">
                                        <div class="col-md-8">
                                            [ll.form-controls.input id="branch_name" autocomplete="off" name="branch_name" label="Branch Name" value="" required="1"  /]
                                        </div>
                                        <div class="col-md-4">
                                            [array branch_status]
                                                [array.new]
                                                    [item display]Enabled[/item]
                                                    [item value]1[/item]
                                                    [item selected]1[/item]
                                                [/array]
                                                [array.new]
                                                    [item display]Disable[/item]
                                                    [item value]0[/item]
                                                [/array]
                                            [/array]
                                            [ll.form-controls.select "{branch_status}" name="branch_status" label="Status" group_class="mx-3 my-0" label_class="form-label mx-3"/]
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            [ll.form-controls.textarea  id="address" name="address" label="Address" value="" length="255" /]
                                        </div>
                                    </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        [ll.form-controls.input id="location" autocomplete="off" name="location" label="Location" value="" required="1"  /]
                                    </div>
                                    <div class="col-md-4">
                                        [ll.form-controls.input id="city" autocomplete="off" name="city" label="city" value="" required="1"  /]
                                    </div>
                                    <div class="col-md-4">
                                        [ll.form-controls.input id="pin" autocomplete="off" name="pin" label="Pincode" value="" required="1"  /]
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        [ll.form-controls.input id="email" autocomplete="off" name="email" label="Email" value="" required="1"  /]
                                    </div>
                                    <div class="col-md-4">
                                        [ll.form-controls.input id="contact_no" autocomplete="off" name="contact_no" label="Contact No" value="" required="1"  /]
                                    </div>
                                    <div class="col-md-4">

                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12">
                                        [ll.form-controls.file accept="image/*" id="logo" name="logo" label="Logo" value="" required="1"/]
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12 text-center">
                                        [ll.form-controls.submit  id="permission_submit" name="permission_submit" value="Create Branch" /]
                                    </div>
                                </div>

                                    [/form]

                            </div>
                        </div>
                    </div>
                    <div class="col"></div>
                </div>
            </div>

            <div class="col-xl-6">

            </div>
        </div>

    </div>
</div>
[actions.footer]
<script type="text/javascript">
    //Permissions Form Handler
    const formHandler = new yantra.FormHandler('#branch_create', true,{
        onSuccess:function (response,handler,frm){
            return true;
        },
        onFormError:function (response,handler,frm){
            return true;
        },
        onError:function (error){

        }
    });
</script>
[/actions]
