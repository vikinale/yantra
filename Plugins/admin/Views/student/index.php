<div class="content-wraper-area">
    <!-- Basic Form area Start -->
    <div class="container-fluid">
        <div class="row g-4">
            <div class="col-12">
                <div class="card">
                    [ll.pc.breadcrumb "{breadcrumb}" title="Student List" /]
                </div>
            </div>

            <div class="col-xl-12">
                <div class="card card-h-100">
                    <div class="card-body">
                        <div class="card-title">
                            <h2 class="card-title">Student List</h2>
                        </div>
                        <div class="w-100 overflow-auto">
                            <div  class="table-filters d-md-flex d-lg-flex d-sm-flex flex-lg-row-reverse flex-sm-column-reverse">
                                <div>[ll.form-controls.input id="branch_name" name="branch_name" label="Branch Name" /]</div>
                            </div>
                            <div id="list-table">
                                <div class="table-container bg-white mx-sm-0">
                                    <table class="table table-bordered table-striped table-light"></table>
                                </div>
                                <div class="pagination-container"></div>
                            </div>
                            [actions.footer]
                            <script type="text/javascript">
                                //Permission Data Table
                                const config = { perPage: 10, orderColumns: { 'id': 'ASC' } };
                                let table = new yantra.JsonTable('#list-table',
                                    app.url('admin/students/table'),
                                    Array(
                                        {name:'id',label:'ID', sortable:true, render:function (value,rec){
                                                let url = app.url('admin/students/'+value);
                                                return `<a class="btn btn-sm btn-link" href="${url}"> ${value} </a>`;
                                            }},
                                        { name: 'name', label: 'Name', sortable: true,val:function (){return document.getElementById('branch_name')?.value;} },
                                        { name: 'status', label: 'Status', sortable: false,val:function (){return document.getElementById('branch_status')?.value;} }
                                    ),config);

                                const input1 = document.getElementById('branch_name');
                                const input2 = document.getElementById('branch_status');
                                input1.addEventListener('change', function(event) {
                                    table.refresh();
                                });
                                input2.addEventListener('change', function(event) {
                                    table.refresh();
                                });
                            </script>
                            [/actions]
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

