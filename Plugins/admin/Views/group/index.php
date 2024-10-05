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
                            <h2 class="card-title">Student Group List</h2>
                        </div>
                        <div class="w-100 overflow-auto">
                            <div  class="table-filters d-md-flex d-lg-flex d-sm-flex flex-lg-row-reverse flex-sm-column-reverse">
                                <div class="p-1">[ll.form-controls.input type="search" id="branch_name" class="table-filter" name="branch_name" label="Branch Name" /]</div>
                                <div class="p-1">[ll.form-controls.input type="search" id="group_name_filter" class="table-filter" name="group_name_filter" label="Group Name" /]</div>
                                <div class="p-1">[ll.form-controls.select "{year_select_list}" name="year_filter" class="table-filter"  id="year_filter" label="Year"  /]</div>
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
                            <h4 class="card-title"><?= $rec->group_name??'Add New'; ?></h4>
                        </div>
                        [form.classroom_form post action="{site_url}/admin/student-group/create"]
                            <input type="hidden" name="rec_id" value="<?= $rec->ID??0; ?>" />
                            <div class="error-container mb-3"></div>
                            <div class="message-container mb-3"></div>
                            [ll.form-controls.select "{branch_select_list}" name="branch"  id="branch" label="Branch" selected="<?= $rec->branch_id??''; ?>" /]
                            [ll.form-controls.select "{year_select_list}" name="year_id" id="year_id" label="Year"  selected="<?= $rec->academic_year??''; ?>" /]
                            [ll.form-controls.input name="group_name" id="group_name" label="Group Name" value="<?= $rec->group_name??''; ?>"  /]
                            <div class="mt-4">
                                [ll.form-controls.submit name="classroom_submit" id="classroom_submit" value="<?= ($rec->ID??null) == null?"Create New":"Update"; ?>" /]
                                <?php if(($rec->ID??null) !=null): ?>
                                    [ll.form-controls.link_button url="<?= site_url('admin/student-group'); ?>"  id="btn_reset" value="Reset" /]
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
        app.url('admin/student-group/table'),
        Array(
            {name:'id',label:'ID', order:'ASC', sortable:true, render:function (value,rec){
                    let url = app.url('admin/student-group/'+value);
                    return `<a class="btn btn-sm btn-link" href="${url}"> ${value} </a>`;
                }},
            { name: 'name', label: 'Name', sortable: true,val:function (){return document.getElementById('group_name_filter')?.value;} },
            { name: 'branch', label: 'Branch', sortable: true,val:function (){return document.getElementById('branch_name')?.value;}},
            { name: 'year_name', label: 'Year', sortable: true, val:function (){
                    const selectElement = document.getElementById('year_filter');
                    const text =  selectElement.options[selectElement.selectedIndex].text;
                    return text==="Select"?'':text;
            }}
        ),config);

    // Select all input elements with class 'table-filter'
    const inputs = document.querySelectorAll('input.table-filter');
    // Select all select elements with class 'table-filter'
    const selects = document.querySelectorAll('select.table-filter');

    // Add 'input' event listener for each input element
    inputs.forEach(function(input) {
        input.addEventListener('input', function(event) {
            table.refresh(); // Refresh the table when input changes
        });
    });

    // Add 'change' event listener for each select element
    selects.forEach(function(select) {
        select.addEventListener('change', function(event) {
            table.refresh(); // Refresh the table when select value changes
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