<div class="content-wraper-area">
    <!-- Basic Form area Start -->
    <div class="container-fluid">
        <div class="row g-4">
            <div class="col-12">
                <div class="card">
                    [ll.pc.breadcrumb "{breadcrumb}" title="Create Student" /]
                </div>
            </div>

            <div class="col-12">
                <div class="row">
                    <div class="col"></div>
                    <div class="col-xl-6">
                        <div class="card card-h-100">
                            <div class="card-body">
                                <div class="card-title">
                                    <h4 class="card-title">Add New Student</h4>
                                </div>
                                [array student_status]
                                [array.new]
                                [item display]Current[/item]
                                [item value]current[/item]
                                [/array]
                                [array.new]
                                [item display]Ex-Student[/item]
                                [item value]ex-student[/item]
                                [/array]
                                [array.new]
                                [item display]New[/item]
                                [item value]new[/item]
                                [/array]
                                [array.new]
                                [item display]Not Attending[/item]
                                [item value]not-attending[/item]
                                [/array]
                                [/array]

                                [form.student_create post action="{site_url}/admin/students/save"]
                                <div class="error-container mb-3"></div>
                                <div class="message-container mb-3"></div>


                                <!-- Student ID -->
                                <div class="row">
                                    <div class="col-md-4">
                                        [ll.form-controls.input id="student_id" autocomplete="off" name="student_id" label="Student ID" value="" /]
                                    </div>
                                    <div class="col-md-4">
                                        [ll.form-controls.select "{student_status}" name="status" selected="current" label="Status"  /]
                                    </div>
                                    <div class="col-md-4">
                                        [ll.form-controls.select "{year_select_list}" name="year" id="year" label="Year" selected="<?= $rec->academic_year??''; ?>" /]
                                    </div>
                                </div>
                                <!-- School, Branch, Class, and Division -->
                                <div class="row">
                                    <div class="col-md-4">
                                        [ll.form-controls.select "{branch_select_list}" name="branch" id="branch" label="Branch" selected="<?= $rec->branch_id??''; ?>" /]
                                    </div>
                                    <div class="col-md-4">
                                        [ll.form-controls.select "{class_select_list}" name="class" id="class" label="Class" selected="<?= $rec->class_id??''; ?>" /]
                                    </div>
                                    <div class="col-md-4">
                                        [ll.form-controls.select "{division_select_list}" name="division" id="division" label="Division" selected="<?= $rec->division_id??''; ?>" /]
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-8">
                                        [ll.form-controls.input id="classroom" autocomplete="off" name="classroom" label="Classroom" value="" readonly="1" required="1" /]
                                    </div>
                                    <div class="col-md-4">
                                        [ll.form-controls.input type="number" id="roll_no" autocomplete="off" name="roll_no" label="Roll No" value="" /]
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12">
                                        <hr />
                                    </div>
                                </div>
                                <!-- Username and Email -->
                                <div class="row">
                                    <div class="col-md-6">
                                        [ll.form-controls.input id="username" autocomplete="off" name="username" label="Username" value="" required="1" /]
                                    </div>
                                    <div class="col-md-6">
                                        [ll.form-controls.input id="student_email" autocomplete="off" name="student_email" label="Email" value="" required="1" /]
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-12">
                                        <hr />
                                    </div>
                                </div>
                                <!-- First Name and Last Name -->
                                <div class="row">
                                    <div class="col-md-4">
                                        [ll.form-controls.input id="first_name" autocomplete="off" name="first_name" label="First Name" value="" required="1" /]
                                    </div>
                                    <div class="col-md-4">
                                        [ll.form-controls.input id="middle_name" autocomplete="off" name="middle_name" label="Middle Name" value="" required="1" /]
                                    </div>
                                    <div class="col-md-4">
                                        [ll.form-controls.input id="last_name" autocomplete="off" name="last_name" label="Last Name" value="" required="1" /]
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-3">
                                        [ll.form-controls.input id="birth_date" autocomplete="off" name="birth_date" label="Birth Date" value="" type="date" /]
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-12">
                                        <hr />
                                    </div>
                                </div>
                                <!-- Parent Details -->
                                <div class="row">
                                    <div class="col-md-6">
                                        [ll.form-controls.input id="father_name" autocomplete="off" name="father_name" label="Father's Name" value="" /]
                                    </div>
                                    <div class="col-md-6">
                                        [ll.form-controls.input id="mother_name" autocomplete="off" name="mother_name" label="Mother's Name" value="" /]
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        [ll.form-controls.input id="father_email" autocomplete="off" name="father_email" label="Father's Email" value="" /]
                                    </div>
                                    <div class="col-md-6">
                                        [ll.form-controls.input id="mother_email" autocomplete="off" name="mother_email" label="Mother's Email" value="" /]
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        [ll.form-controls.input id="father_mobile" autocomplete="off" name="father_mobile" label="Father's Mobile" value="" /]
                                    </div>
                                    <div class="col-md-6">
                                        [ll.form-controls.input id="mother_mobile" autocomplete="off" name="mother_mobile" label="Mother's Mobile" value="" /]
                                    </div>
                                </div>


                                <div class="row">
                                    <div class="col-12">
                                        <hr />
                                    </div>
                                </div>
                                <!-- Address and Location -->
                                <div class="row">
                                    <div class="col-md-12">
                                        [ll.form-controls.textarea id="address" name="address" label="Address" value="" length="255" /]
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        [ll.form-controls.input id="location" autocomplete="off" name="location" label="Location" value="" /]
                                    </div>
                                    <div class="col-md-6">
                                        [ll.form-controls.input id="pin" autocomplete="off" name="pin" label="Pin code" value="" /]
                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <div class="row">
                                    <div class="col-12 text-center mt-4">
                                        [ll.form-controls.submit id="student_submit" class="btn-primary" name="student_submit" value="Create Student" /]
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
    const formHandler = new yantra.FormHandler('#student_create', true,{
        onSuccess:function (response,handler,frm){
            console.log(response);
            return true;
        },
        onFormError:function (response,handler,frm){
            console.log(response);
            return true;
        },
        onError:function (error){
            console.log(error);
        }
    });

    $("#branch,#class,#division,#year").on('change',function(){
        let b =  $("#branch option:selected").text();
        let a = $("#year option:selected").text();
        let c = $("#class").val();
        let d = $("#division option:selected").text();
        d = d.replace(/Select/g, '');
        b = b.replace(/Select/g, '');
        a = a.replace(/-/g, '').replace(/Select/g, '');;
        if(c.length ===1){
            c = `0${c}`;
        }
        $("#classroom").val(`${b}${c}${d}${a}`);
    });
</script>
[/actions]
