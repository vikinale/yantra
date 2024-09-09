<div class="content-wraper-area">
    <!-- Basic Form area Start -->
    <div class="container-fluid">
        <div class="row g-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body card-breadcrumb">
                        <div class="page-title-box d-flex align-items-center justify-content-between">
                            <h4 class="mb-0">Users</h4>
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0">
                                    <li class="breadcrumb-item"><a href="javascript: void(0);">Users</a>
                                    </li>
                                    <li class="breadcrumb-item active">create users</li>
                                </ol>
                            </div>
                        </div>
                    </div>
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
                                <form id="create-user" method="POST" action="<?= site_url('admin/users/create') ?>">
                                    <!-- Error message container -->
                                    <div id="error-container text-danger"></div>

                                    <div class="mb-3">
                                        <label class="form-label" for="username">Username</label>
                                        <input type="text" class="form-control" id="username" name="username" required/>
                                        <div class="form-text text-danger field-error"></div> <!-- Placeholder for error -->
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label" for="email">Email</label>
                                        <input type="email" class="form-control" name="email" id="email" required>
                                        <div class="form-text text-danger field-error"></div> <!-- Placeholder for error -->
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label" for="password">Password</label>
                                        <input type="password" class="form-control" name="password" id="password" required>
                                        <div class="form-text text-danger field-error"></div> <!-- Placeholder for error -->
                                    </div>

                                    <div class="mb-3">
                                        <label class="col-md-2 col-form-label" for="user-role">Select</label>
                                        <div class="col-md-4">
                                            <select class="form-select" id="user-role" name="role" required>
                                                <option value="">Select</option>
                                                <?php foreach ($roles??[] as $role): ?>
                                                    <option value="<?= $role['id'] ?>"><?= $role['role_name'] ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="form-text text-danger field-error"></div> <!-- Placeholder for error -->
                                        </div>
                                    </div>

                                    <div class="mt-4">
                                        <button type="submit" class="btn btn-primary w-md">Create User</button>
                                    </div>
                                </form>

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
    // Initialize the FormHandler
    const formHandler = new yantra.FormHandler('#create-user', true);
    // Add event listeners for custom form events
    formHandler.addEventListener('success', (response) => {
        if (response.ok) {
            let res = JSON.parse(response.content);
            if (res.status) {
                // Success handling
                console.log('User created successfully');
            } else {
                if (typeof res.errors === 'object' && res.errors !== null) {
                    // Clear previous errors
                    document.querySelectorAll('.field-error').forEach(errorElement => {
                        errorElement.textContent = '';
                    });
                    document.getElementById('error-container').innerHTML = '';

                    // Loop through the error object and display each error
                    for (let field in res.errors) {
                        if (res.errors.hasOwnProperty(field)) {
                            let errorMessage = res.errors[field];
                            let fieldElement;

                            // Find the corresponding input/select element
                            if (field === 'username') {
                                fieldElement = document.querySelector('input[name="username"]');
                            } else if (field === 'email') {
                                fieldElement = document.querySelector('input[name="email"]');
                            } else if (field === 'password') {
                                fieldElement = document.querySelector('input[name="password"]');
                            } else if (field === 'role') {
                                fieldElement = document.querySelector('select[name="role"]');
                            }

                            if (fieldElement) {
                                // Find the closest '.field-error' container to display the error
                                const errorContainer = fieldElement.closest('.mb-3').querySelector('.field-error');
                                if (errorContainer) {
                                    errorContainer.textContent = errorMessage;
                                }
                            } else {
                                // General errors (if any) are appended to the error-container
                                document.getElementById('error-container').innerHTML += `<p class="text-danger">${errorMessage}</p>`;
                            }
                        }
                    }
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
