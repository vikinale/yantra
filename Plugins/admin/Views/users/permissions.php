<div class="content-wraper-area">
    <!-- Basic Form area Start -->
    <div class="container-fluid">
        <div class="row g-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body card-breadcrumb">
                        <div class="page-title-box d-flex align-items-center justify-content-between">
                            <h4 class="mb-0">User Permissions</h4>
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0">
                                    <li class="breadcrumb-item"><a href="javascript: void(0);">School</a>
                                    </li>
                                    <li class="breadcrumb-item active">User Permissions</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-7">
                <div class="card card-h-100">
                    <div class="card-body">
                        <div class="card-title">
                            <h2 class="card-title">User Permissions</h2>
                        </div>
                        <div class="w-100 overflow-auto">
                            <div id="permissions-table">
                                <div class="table-container"></div>
                                <div class="pagination-container"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-5">
                <div class="card">
                    <div class="card-body">

                        [ll.form-controls.input floating="0" type="email" label="Full Name" id="full-name" name="full_name" value="" required="true" readonly="1" /]
                        [ll.form-controls.input floating="1" type="tel" label="Contact No" id="contact" name="contact" value="" required="0"  /]
                        [ll.form-controls.select label="Contact No" id="contact" name="contact" value="" required="0"  /]
                        [ll.form-controls.file type="tel" label="Contact No" id="contact" name="contact" value="" required="0"  /]

                        <?php var_dump($permission); ?>
                    </div>
                </div>
            </div>

        </div>

    </div>
</div>
[actions.header]
[/actions]
[actions.footer]
<script type="text/javascript">
    const config = {
        perPage: 10,
        orderColumns: { 'ID': 'ASC' }
    };
    console.log('viki');
    const table = new yantra.JsonTable('#permissions-table',
        app.url('admin/users/permissions-table'),
        Array({name: 'ID',db_name:"id", label: 'ID', sortable: true, order:'ASC',render:function (value,rec)
               {
                    let url = app.url('admin/users/permissions/'+value);
                    return `<a class="btn btn-sm btn-link" href="${url}"> ${value} </a>`;
               }},
               { name: 'permission_name', label: 'Permission', sortable: true })
        ,config);
</script>
[/actions]