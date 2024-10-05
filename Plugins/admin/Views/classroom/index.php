<div class="content-wraper-area">
    <!-- Basic Form area Start -->
    <div class="container-fluid">
        <div class="row g-4">
            <div class="col-12">
                <div class="card">
                    [ll.pc.breadcrumb "{breadcrumb}" title="Classroom List" /]
                </div>
            </div>

            <div class="col-xl-7">
                <div class="card card-h-100">
                    <div class="card-body">
                        <div class="card-title">
                            <h2 class="card-title">Classroom List</h2>
                        </div>
                        <div class="w-100 overflow-auto">
                            <div  class="table-filters d-md-flex d-lg-flex d-sm-flex flex-lg-row-reverse flex-sm-column-reverse">
                                <div class="p-1">[ll.form-controls.input type="search" id="branch_name" class="table-filter" name="branch_name" label="Branch Name" /]</div>
                                <div class="p-1">[ll.form-controls.input type="search" id="year_name" class="table-filter" name="year_name" label="Year" /]</div>
                                <div class="p-1">[ll.form-controls.input type="search" id="division_name" class="table-filter" name="division_name" label="Division" /]</div>
                                <div class="p-1">[ll.form-controls.input type="search" id="class_name" class="table-filter" name="class_name" label="Class" /]</div>
                            </div>
                            <div id="branch-table">
                                <div class="table-container bg-white mx-sm-0">
                                    <table class="table table-bordered table-striped table-light"></table>
                                </div>
                                <div class="pagination-container"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-5">
                <div class="card">
                    <div class="card-body">
                        <div class="card-title">
                            <h4 class="card-title"><?= $rec->shortname??'Add New'; ?></h4>
                        </div>
                        [form.classroom_form post action="{site_url}/admin/classrooms/create"]
                            <input type="hidden" name="rec_id" value="<?= $rec->ID??0; ?>" />
                            <div class="error-container mb-3"></div>
                            <div class="message-container mb-3"></div>
                            [ll.form-controls.select "{branch_select_list}" name="branch"  id="branch" label="Branch" selected="<?= $rec->branch_id??''; ?>" /]
                            [ll.form-controls.select "{class_select_list}" name="class"  id="class" label="Class"  selected="<?= $rec->class_id??''; ?>" /]
                            [ll.form-controls.select "{division_select_list}" name="division"  id="division" label="Division"  selected="<?= $rec->division_id??''; ?>" /]
                            [ll.form-controls.select "{year_select_list}" name="year" id="year" label="Year"  selected="<?= $rec->academic_year??''; ?>" /]
                            [ll.form-controls.input name="classroom_name" id="classroom_name" label="Classroom Name" disabled="1" value="<?= $rec->shortname??''; ?>"  /]
                            <div class="mt-4">
                                [ll.form-controls.submit name="classroom_submit" id="classroom_submit" value="<?= ($rec->ID??null) == null?"Create New":"Update"; ?>" /]
                                <?php if(($rec->ID??null) !=null): ?>
                                    [ll.form-controls.link_button url="<?= site_url('admin/classrooms'); ?>"  id="btn_reset" value="Reset" /]
                                <?php endif; ?>
                            </div>
                        [/form]
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
[actions.footer]
<script type="text/javascript">
    const config = { perPage: 10, orderColumn: 'id'};
    let table = new yantra.JsonTable('#branch-table',
        app.url('admin/classrooms/table'),
        Array(
            {name:'id',label:'ID', order:'ASC', sortable:true, render:function (value,rec){
                    let url = app.url('admin/classrooms/'+value);
                    return `<a class="btn btn-sm btn-link" href="${url}"> ${value} </a>`;
                }},
            { name: 'shortname', label: 'Name', sortable: true },
            { name: 'class', label: 'Class', sortable: true,val:function (){return document.getElementById('class_name')?.value;} },
            { name: 'division', label: 'Division', sortable: true,val:function (){return document.getElementById('division_name')?.value;} },
            { name: 'year_name', label: 'Year', sortable: true,val:function (){return document.getElementById('year_name')?.value;} },
            { name: 'branch', label: 'Branch', sortable: true,val:function (){return document.getElementById('branch_name')?.value;} }
        ),config);

    const inputs = document.getElementsByClassName('table-filter');
    Array.from(inputs).forEach(function(input) {
        input.addEventListener('input', function(event) {
            table.refresh();
        });
    });

    //Permissions Form Handler
    const formHandler = new yantra.FormHandler('#classroom_form', true,{
        onSuccess:function (response,handler,frm){
            table.refresh();
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