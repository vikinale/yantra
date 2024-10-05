<div class="content-wraper-area">
    <!-- Basic Form area Start -->
    <div class="container-fluid">
        <div class="row g-4">
            <div class="col-12">
                <div class="card">
                    [array breadcrumb_list]
                    [array.new]
                        [item href][get site_url/]/admin[/item]
                        [item text]Dashboard[/item]
                    [/array]
                    [array.new]
                        [item active]active[/item]
                        [item text]Users[/item]
                    [/array]
                    [/array]
                    [ll.pc.breadcrumb "{breadcrumb_list}" title="Users" /]
                </div>
            </div>

            <div class="col-xl-12">
                <div class="card card-h-100">
                    <div class="card-body">
                        <div class="card-title">
                            <h2 class="card-title">Users</h2>
                        </div>
                        <div class="w-100 overflow-auto">
                            <div id="permissions-filters">
                               <div class="container">
                                   <div class="row">
                                       <div class="col-md-4">[ll.form-controls.input id="username" name="username" label="Username" /]</div>
                                       <div class="col-md-4">[ll.form-controls.input id="first_name" name="first_name" label="First Name"  /]</div>
                                       <div class="col-md-4">[ll.form-controls.input id="lst_name" name="lst_name" label="Last Name"  /]</div>
                                   </div>
                               </div>
                            </div>
                            <div id="users-table">
                                <div class="table-container bg-white">
                                    <table class="table table-bordered table-striped table-light"></table>
                                </div>
                                <div class="pagination-container"></div>
                            </div>
                            [actions.footer]
                            <script type="text/javascript">
                                //Permission Data Table
                                const config = { perPage: 3, orderColumns: { 'username': 'ASC' } };
                                const table = new yantra.JsonTable('#users-table',
                                    app.url('admin/users/table'),
                                    Array(
                                        { name: 'username', label: 'Username', sortable: true, order:'ASC',
                                            render:function (value,rec){
                                                let url = app.url('admin/users/'+rec.ID);
                                                return `<a class="btn btn-sm btn-link" href="${url}"> ${value} </a>`;
                                            },
                                            val:function (){
                                                return document.getElementById('username')?.value;
                                            }
                                        },
                                        { name: 'user_email', label: 'Email', sortable: true,val:function (){return document.getElementById('user_email')?.value;} },
                                        { name: 'first_name', label: 'First name', sortable: false,val:function (){return document.getElementById('first_name')?.value;} },
                                        { name: 'last_name', label: 'Last name', sortable: false,val:function (){return document.getElementById('lst_name')?.value;}  }
                                    ),config);
                            </script>
                            [/actions]
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>
</div>

