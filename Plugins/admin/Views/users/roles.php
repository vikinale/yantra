<div class="content-wraper-area">
    <!-- Basic Form area Start -->
    <div class="container-fluid">
        <div class="row g-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body card-breadcrumb">
                        <div class="page-title-box d-flex align-items-center justify-content-between">
                            <h4 class="mb-0">User Roles</h4>
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0">
                                    <li class="breadcrumb-item"><a href="javascript: void(0);">School</a>
                                    </li>
                                    <li class="breadcrumb-item active">User Roles</li>
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
                            <h4 class="card-title">User Roles</h4>
                        </div>
                        <div class="pb-4">
                            <input type="button" class="btn btn-outline-primary btn-edit-role"
                                   data-bs-toggle="modal"
                                   data-bs-target="#newUserRole"
                                   value="New Role" />
                        </div>
                        <div class="w-100 overflow-auto">
                            <table id="datatable" class="table table-bordered dt-responsive nowrap data-table-area">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($roles??[] as $r): ?>
                                    <tr>
                                        <td><?= $r->id; ?></td>
                                        <td><?= $r->name; ?></td>
                                        <td><?= $r->description; ?></td>
                                        <td>
                                            <a href="<?= site_url('admin/users/roles/'); ?><?= $r->id; ?>" class="btn btn-info btn-sm btn-view-role">View</a>
                                            <input type="button" class="btn btn-danger btn-sm btn-edit-role" value="Delete" />
                                        </td>
                                    </tr>
                                <?php endforeach; $r = null;    ?>
                                </tbody>

                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-5">
                <div class="card">
                    <div class="card-body">
                        <div class="card-title">
                            <h4 class="card-title">Role <?= $role->name??'New'; ?></h4>
                        </div>
                        <form id="user-role" method="post" action="<?= site_url('admin/users/roles/create'); ?>">
                            <input type="hidden" name="role_id" value="<?= $role->id??0; ?>" />
                            <div class="error-container mb-3"></div>
                            <div class="message-container mb-3"></div>
                            <div class="mb-3">
                                <label class="form-label" for="user-role-name">Role Name</label>
                                <input type="text" class="form-control" id="user-role-name" name="name" value="<?= $role->name??''; ?>" <?= $role?->id==null?"":"readonly"; ?> required>
                                <div class="form-text text-danger field-error"></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="user-role-description">Role Description</label>
                                <textarea class="form-control" id="user-role-description" name="description" rows="2"><?= $role->description??''; ?></textarea>
                                <div class="form-text text-danger field-error"></div>
                            </div>
                            <?php if(isset($permissions)):
                                $rolePermissions = json_decode($role->permissions)??[];
                                ?>
                            <div class="mb-3">
                                <div class="user-permissions-list">
                                <?php foreach ($permissions as $p): ?>
                                    <label class="label p-3 border border-1px" for="permission-<?= $p['id'] ?>">
                                        <input id="permission-<?= $p['id'] ?>" type="checkbox" name="permissions[]" value="<?= $p['id'] ?>" <?= in_array($p['id'],$rolePermissions)?'checked':'';  ?> /> <?= $p['permission_name']; ?>
                                    </label>
                                <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary w-md"><?= $role?->id==null?"Create New Role":"Update"; ?></button>
                            <?php if($role?->id!=null): ?>
                                    <a href="<?= site_url('admin/users/roles'); ?>" class="btn btn-secondary">Reset</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>

    </div>
</div>
<!-- Modal -->
<div class="modal fade" id="newUserRole" data-bs-backdrop="static"
     data-bs-keyboard="false" tabindex="-1" aria-labelledby="new-role-model-title"
     aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content p-3">
            <form id="form-new-role"  method="post" action="<?= site_url('admin/users/roles/create'); ?>">
                <div class="modal-header">
                    <h4 class="modal-title fs-5" id="new-role-model-title">New User Role</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="new-role-name">Role Name</label>
                        <input type="text" class="form-control" id="new-role-name" name="name">
                        <div class="form-text text-danger field-error"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="new-role-description">Role Description</label>
                        <textarea class="form-control" id="new-role-description" name="description" rows="2"></textarea>
                        <div class="form-text text-danger field-error"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="error-container message-container txt-left flex-grow-1"></div>
                    <button type="reset" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
[actions.header]
<!-- These plugins only need for the run this page -->
<link rel="stylesheet" href="<?= theme_url(); ?>css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="<?= theme_url(); ?>css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="<?= theme_url(); ?>css/select.dataTables.min.css">
[/actions]
[actions.footer]
<!-- These plugins only need for the run this page -->
<script src="<?= theme_url(); ?>js/jquery.dataTables.min.js"></script>
<script src="<?= theme_url(); ?>js/dataTables.bootstrap5.min.js"></script>
<script src="<?= theme_url(); ?>js/dataTables.responsive.min.js"></script>
<script src="<?= theme_url(); ?>js/dataTables.buttons.min.js"></script>
<script src="<?= theme_url(); ?>js/buttons.print.min.js"></script>
<script src="<?= theme_url(); ?>js/pdfmake.min.js"></script>
<script src="<?= theme_url(); ?>js/vfs_fonts.js"></script>
<script src="<?= theme_url(); ?>js/buttons.html5.min.js"></script>
<script src="<?= theme_url(); ?>js/jszip.min.js"></script>

<script type="text/javascript">
    // Initialize the FormHandler
    const formHandler = new yantra.FormHandler('#form-new-role', true);
    const formHandler1 = new yantra.FormHandler('#user-role', true);
    // Add event listeners for custom form events
    function handle_user_form(response, handler){
        if (response.ok) {
            let res = JSON.parse(response.content);
            if (res.status) {
                // Success handling
                console.log('User created successfully');
            } else {
                let formElement = document.querySelector(`#${handler.form.id}`);

                if (typeof res.errors === 'object' && res.errors !== null) {
                    // Clear previous errors
                    formElement.querySelectorAll(`.field-error`).forEach(errorElement => {
                        errorElement.textContent = '';
                    });
                    formElement.querySelector(`.error-container`).innerHTML = '';

                    // Loop through the error object and display each error
                    for (let field in res.errors) {
                        if (res.errors.hasOwnProperty(field)) {
                            let errorMessage = res.errors[field];
                            let fieldElement;

                            // Find the corresponding input/select element
                            if (field === 'name') {
                                fieldElement = formElement.querySelector(`input[name="name"]`);
                            } else if (field === 'description') {
                                fieldElement = formElement.querySelector(`input[name="description"]`);
                            }

                            if (fieldElement) {
                                // Find the closest '.field-error' container to display the error
                                const errorContainer = fieldElement.closest('.mb-3').querySelector(`.field-error`);
                                if (errorContainer) {
                                    errorContainer.textContent = errorMessage;
                                }
                            } else {
                                // General errors (if any) are appended to the error-container
                                formElement.querySelector(`.error-container`).innerHTML += `<p class="text-danger">${errorMessage}</p>`;
                            }
                        }
                    }
                }
                if (res.message !== null && res.message !== ""){
                    formElement.querySelector(`.message-container`).innerHTML = `<p class="text-info">${res.message}</p>`;
                }
            }
        }
    }
    formHandler.addEventListener('success', handle_user_form);
    formHandler1.addEventListener('success',  handle_user_form);

    formHandler.addEventListener('error', (error) => {
        console.error('Form submission error:', error);
        yantra.globalQueue = yantra.globalQueue.catch(() => {});
    });

    formHandler1.addEventListener('error', (error) => {
        console.error('Form submission error:', error);
        yantra.globalQueue = yantra.globalQueue.catch(() => {});
    });
</script>
[/actions]