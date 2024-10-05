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
                                    <h4 class="card-title">Create users</h4>
                                </div>
                                    [form.userform post action="{site_url}/admin/users/create"]
                                    <div class="error-container mb-3"></div>
                                    <div class="message-container mb-3"></div>
                                        [ll.form-controls.input id="username" autocomplete="off" name="username" label="Username" value="" required="1" pattern="^[a-zA-Z][a-zA-Z0-9_]{2,15}$" /]
                                        [ll.form-controls.input type="email"  id="email" name="email" label="Email" value="" required="1"/]
                                        [ll.form-controls.input type="password" autocomplete="new-password"  id="password" name="password" label="Password" value="" required="1"/]
                                        [array roles]
                                        <?php foreach ($roles??[] as $role): ?>
                                            [array.new]
                                                [item value]<?= $role->id ?>[/item]
                                                [item display]<?= $role->name ?>[/item]
                                            [/array]
                                        <?php endforeach; ?>
                                        [/array]
                                        [ll.form-controls.select "{roles}" id="user-role" name="user-role[]" label="User Role" value="" required="1" multiple="1" size="6"/]
                                        [ll.form-controls.submit  id="permission_submit" name="permission_submit" value="Save" /]
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
    const formHandler = new yantra.FormHandler('#userform', true);
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
                            let fieldElement = formElement.querySelector(`[id="${field}"]`);

                            if (fieldElement) {
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
