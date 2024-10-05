<div class="content-wraper-area">
    <!-- Basic Form area Start -->
    <div class="container-fluid">
        <div class="row g-4">
            <div class="col-12">
                <div class="card">
                    [array breadcrumb_list]
                        [array.new]
                            [item href][get site_url/]/admin/users[/item]
                            [item text]Users[/item]
                        [/array]
                        [array.new]
                            [item active]active[/item]
                            [item text]Permissions[/item]
                     [/array]
                    [/array]
                    [ll.pc.breadcrumb "{breadcrumb_list}" title="Permissions" /]
                </div>
            </div>

            <div class="col-xl-7">
                <div class="card card-h-100">
                    <div class="card-body">
                        <div class="card-title">
                            <h2 class="card-title">Permissions</h2>
                        </div>
                        <div class="w-100 overflow-auto">
                            <div id="permissions-table">
                                <div class="table-container bg-white">
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
                        <div class="card-title d-flex">
                            <div><h2 class="card-title">Permission <?= ($permission?->permission_name)?'Update':'New'; ?></h2></div>
                            <div class="text-right flex-grow-1">
                                <?php if(isset($permission->id)): ?>
                                     <a href="[get env.site_url/]/admin/users/permissions" class="btn btn-outline-info">Add New</a>
                                <?php endif; ?></div>
                        </div>

                        [form.permission post action="{site_url}/admin/users/permissions/create"]
                            <div class="error-container mb-3"></div>
                            <div class="message-container mb-3"></div>
                            <?php if(isset($permission->id)): ?>
                            [ll.form-controls.hidden name="permission_id" value="<?= $permission?->id; ?>" /]
                            <?php endif; ?>
                            [ll.form-controls.input  id="permission_name" name="permission_name" label="Name" value="<?= $permission?->permission_name; ?>" /]
                            [ll.form-controls.textarea  id="permission_description" name="permission_description" label="Description" value="<?= $permission?->permission_description; ?>" length="255" /]
                            [ll.form-controls.submit  id="permission_submit" name="permission_submit" value="Save" /]
                        [/form]

                    </div>
                </div>
            </div>

            <div class="col-xl-12">
                <div class="card">
                    <div class="card-body">
                        [array radio_options]
                            [array.new]
                                [item label]Male[/item]
                                [item id]gender-male[/item]
                                [item value]1[/item]
                            [/array]
                            [array.new]
                                [item label]Female[/item]
                                [item id]gender-female[/item]
                                [item value]0[/item]
                            [/array]
                        [/array]

                        [ll.form-controls.radio_group "{radio_options}" name="gender" label="Gender" main_group_class="d-flex border border-1 py-2" group_class="mx-3 my-0" label_class="form-label mx-3"/]
                        [ll.form-controls.checkbox label="My Label" var1="1" id="check1" value="one" /]
                        [ll.form-controls.textarea  id="sample1" name="sample1" length="255" /]

                        [unset radio_options/]

                        [ll.form-controls.editor id="my-editor1" /]
                        [ll.form-controls.editor id="my-editor2" /]

                    </div>
                </div>
            </div>

        </div>

    </div>
</div>

[actions.footer]
<script type="text/javascript">
    //Permission Data Table
    const config = {
        perPage: 3,
        orderColumn: 'ID'
    };
    const table = new yantra.JsonTable('#permissions-table',
        app.url('admin/users/permissions-table'),
        Array({name: 'ID', label: 'ID', sortable: true, order:'ASC',render:function (value,rec)
               {
                    let url = app.url('admin/users/permissions/'+value);
                    return `<a class="btn btn-sm btn-link" href="${url}"> ${value} </a>`;
               }},
            { name: 'name', label: 'Permission', sortable: true },
            { name: 'description', label: 'Description', sortable: false })
        ,config);

    //Permissions Form Handler
    const formHandler = new yantra.FormHandler('#permission', true);
    formHandler.addEventListener('success', function (response,handler){
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
                            let fieldElement = formElement.querySelector(`input[name="${field}"]`);
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
                if (res.message !== null && res.message !== "" && typeof res.message !== 'undefined'){
                    formElement.querySelector(`.message-container`).innerHTML = `<p class="text-info">${res.message}</p>`;
                    table.refresh()
                }
            }
        }
    });
    formHandler.addEventListener('error', (error) => {
        console.error('Form submission error:', error);
        yantra.globalQueue = yantra.globalQueue.catch(() => {});
    });
</script>
[/actions]