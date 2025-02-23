@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Tareas</h5>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTaskModal">
                    Crear Tarea
                </button>
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Id</th>
                            <th>Título</th>
                            <th>Descripción</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tasksTable">
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Create Task Modal -->
<div class="modal fade" id="createTaskModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Crear nueva tarea</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Agregamos div para mensajes de error -->
                <div id="createErrorMessages" class="alert alert-danger d-none">
                </div>
                <form id="createTaskForm">
                    <div class="mb-3">
                        <label class="form-label">Título</label>
                        <input type="text" class="form-control" name="titulo" required>
                        <div class="invalid-feedback">Por favor ingresa un titulo</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea class="form-control" name="descripcion" required></textarea>
                        <div class="invalid-feedback">Por favor ingresa una descripción</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Estado</label>
                        <select class="form-select" name="estado" required>
                            <option value="0">Pendiente</option>
                            <option value="1">Completada</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="createTask()">Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Task Modal -->
<div class="modal fade" id="editTaskModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar tarea</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Agregamos div para mensajes de error -->
                <div id="editErrorMessages" class="alert alert-danger d-none">
                </div>
                <form id="editTaskForm">
                    <input type="hidden" name="task_id" id="edit_task_id">
                    <div class="mb-3">
                        <label class="form-label">Título</label>
                        <input type="text" class="form-control" name="titulo" id="edit_titulo" required>
                        <div class="invalid-feedback">Por favor ingresa un titulo</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea class="form-control" name="descripcion" id="edit_descripcion" required></textarea>
                        <div class="invalid-feedback">Por favor ingresa una descripción</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Estado</label>
                        <select class="form-select" name="estado" id="edit_estado" required>
                            <option value="0">Pendiente</option>
                            <option value="1">Completada</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="updateTask()">Actualizar</button>
            </div>
        </div>
    </div>
</div>

<script>
// Función helper para mostrar errores
function showErrors(errorDiv, errors) {
    const errorMessages = document.getElementById(errorDiv);
    errorMessages.innerHTML = '';
    
    if (typeof errors === 'string') {
        errorMessages.innerHTML = `<p>${errors}</p>`;
    } else {
        const errorList = Object.values(errors).map(error => 
            `<p>${error}</p>`
        ).join('');
        errorMessages.innerHTML = errorList;
    }
    
    errorMessages.classList.remove('d-none');
}

// Función helper para limpiar errores
function clearErrors(formId, errorDiv) {
    const form = document.getElementById(formId);
    const inputs = form.querySelectorAll('.form-control, .form-select');
    inputs.forEach(input => {
        input.classList.remove('is-invalid');
    });
    document.getElementById(errorDiv).classList.add('d-none');
}

// Función helper para validar formulario
function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('[required]');
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            isValid = false;
        } else {
            input.classList.remove('is-invalid');
        }
    });
    return isValid;
}

window.createTask = function() {
    const form = document.getElementById('createTaskForm');
    clearErrors('createTaskForm', 'createErrorMessages');
    
    if (!validateForm(form)) {
        showErrors('createErrorMessages', 'Por favor ingresa los campos requeridos');
        return;
    }

    const formData = new FormData(form);
    const data = Object.fromEntries(formData);

    fetch('/api/tasks', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(err => Promise.reject(err));
        }
        return response.json();
    })
    .then(() => {
        loadTasks();
        const modal = bootstrap.Modal.getInstance(document.getElementById('createTaskModal'));
        modal.hide();
        form.reset();
        // Mostrar mensaje de éxito
        showAlert('Tarea creada exitosamente!', 'success');
    })
    .catch(error => {
        if (error.errors) {
            showErrors('createErrorMessages', error.errors);
        } else {
            showErrors('createErrorMessages', 'Ha ocurrido un error al guardar la tarea');
        }
    });
};

window.editTask = function(id) {
    fetch(`/api/tasks/${id}`)
        .then(response => response.json())
        .then(task => {
            document.getElementById('edit_task_id').value = task.id;
            document.getElementById('edit_titulo').value = task.titulo;
            document.getElementById('edit_descripcion').value = task.descripcion;
            document.getElementById('edit_estado').value = task.estado ? "1" : "0";
            
            new bootstrap.Modal(document.getElementById('editTaskModal')).show();
        });
};

window.updateTask = function() {
    const form = document.getElementById('editTaskForm');
    clearErrors('editTaskForm', 'editErrorMessages');
    
    if (!validateForm(form)) {
        showErrors('editErrorMessages', 'Por favor ingresa los campos requeridos');
        return;
    }

    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    const taskId = data.task_id;
    delete data.task_id;

    fetch(`/api/tasks/${taskId}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(err => Promise.reject(err));
        }
        return response.json();
    })
    .then(() => {
        loadTasks();
        const modal = bootstrap.Modal.getInstance(document.getElementById('editTaskModal'));
        modal.hide();
        // Mostrar mensaje de éxito
        showAlert('Se actualizo la tarea exitosamente!', 'success');
    })
    .catch(error => {
        if (error.errors) {
            showErrors('editErrorMessages', error.errors);
        } else {
            showErrors('editErrorMessages', 'Ha ocurrido un error al actualizar la tarea');
        }
    });
};

window.deleteTask = function(id) {
    if (confirm('Esta seguro de eliminar la tarea?')) {
        fetch(`/api/tasks/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(() => {
            loadTasks();
            // Mostrar mensaje de éxito
            showAlert('La tarea ha sido eliminada!', 'success');
        })
        .catch(error => {
            showAlert('Ocurrio un error eliminando la tarea', 'danger');
        });
    }
};

// Función para mostrar alertas
function showAlert(message, type) {
    // Crear el elemento de alerta
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.role = 'alert';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;

    // Insertar la alerta al principio de la card-body
    const cardBody = document.querySelector('.card-body');
    cardBody.insertBefore(alertDiv, cardBody.firstChild);

    // Eliminar la alerta después de 3 segundos
    setTimeout(() => {
        alertDiv.remove();
    }, 3000);
}

function loadTasks() {
    fetch('/api/tasks')
        .then(response => response.json())
        .then(tasks => {
            const tasksTable = document.getElementById('tasksTable');
            tasksTable.innerHTML = '';
            
            tasks.forEach(task => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${task.id}</td>
                    <td>${task.titulo}</td>
                    <td>${task.descripcion}</td>
                    <td>
                        <span class="badge bg-${task.estado ? 'success' : 'warning'}">
                            ${task.estado ? 'Completada' : 'Pendiente'}
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="editTask(${task.id})">Editar</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteTask(${task.id})">Eliminar</button>
                    </td>
                `;
                tasksTable.appendChild(row);
            });
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error cargando las tareas', 'danger');
        });
}

// Inicializar la carga de tareas cuando el documento esté listo
document.addEventListener('DOMContentLoaded', loadTasks);
</script>
@endsection