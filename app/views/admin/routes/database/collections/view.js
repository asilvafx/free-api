
const newEntyForm = document.getElementById('newEntyForm');
const token = document.querySelector('#token');
const tableNameInput = document.querySelector("#tbl_name");


//
if(newEntyForm){
    newEntyForm.addEventListener('submit', function(e){
        e.preventDefault();
        const formData = new FormData(newEntyForm);
        const schema = {};
        formData.forEach((value, key) => {
            schema[key] = value;  // Dynamically adds field name and value
        });

        const payload = {
            token: token.value,
            collection: tableNameInput.value,
            schema: schema,  // Now contains all the form inputs
        }

        let uri_request = "database/collections?add-entry";
        fetchRequest(payload, uri_request);
    });

}

// Add Event Listeners
async function fetchRequest(formData, url){
    event.preventDefault();
    hideAlerts();

    try {
        const response = await axios.post("/{{@SITE.uri_backend}}/"+url, formData, {
            headers: {
                "Content-Type": "application/json",
            },
        }).then(response => {
            const data = response.data;
            if (data.status === "success") {
                alert(response.data.message);
                window.location.reload();
                return false;
            } else {
                showAlert("error", response.data.message);
            }
        }).finally(() => {
            let hideEntryModal = document.getElementById('newEntryExit');
            if(hideEntryModal){hideEntryModal.click()};
        });

    } catch (error) {
        showAlert("error", "Error creating table. Please try again later.");
    }

}

const editButtons = document.querySelectorAll('.btn-edit');
editButtons.forEach(button => {
    button.addEventListener('click', function(e) {
        const fieldsString = '{{json_encode(@collection.fields)}}';

        let fields;

        try {
            fields = JSON.parse(fieldsString);
        } catch (error) {
            console.error("Failed to parse fields:", error);
        }
        const row = this.closest('tr'); // Get the closest row
        const id = row.querySelector('td:first-child').textContent; // Assuming the first cell contains the ID
        document.getElementById('editEntryId').value = id; // Set the ID in the hidden input

        // Populate the fields with current values
        fields.forEach(field => {
            const input = document.getElementById(`editEntry-${field.name}`);
            if (input) {
                // Use data attributes to get the current value
                const currentValue = row.querySelector(`td[data-field="${field.name}"]`).textContent;
                input.value = currentValue.trim(); // Set the input value
            }
        });

    });
});

// Event listener for edit form submission
const editEntryForm = document.getElementById('editEntryForm');
if (editEntryForm) {
    editEntryForm.addEventListener('submit', function(e) {
        e.preventDefault();

        // Get form data
        const formData = new FormData(editEntryForm);
        const schema = {};
        formData.forEach((value, key) => {
            schema[key] = value;  // Dynamically adds field name and value
        });

        // Create payload with token, collection name, and data schema
        const payload = {
            token: token.value,
            collection: tableNameInput.value,
            id: document.getElementById('editEntryId').value, // ID of the entry to update
            schema: schema  // Contains all form inputs
        };

        // Send request to update endpoint
        let uri_request = "database/collections?update-entry";
        updateEntry(payload, uri_request);
    });
}

const deleteEntryBtn = document.getElementById('deleteEntryBtn');

if (deleteEntryBtn) {
    deleteEntryBtn.addEventListener('click', function() {
        // Get the entry ID from the hidden input field
        const entryId = document.getElementById('editEntryId').value;
        const tableName = document.getElementById('tbl_name').value;

        // Show confirmation dialog
        if (window.confirm('Are you sure you want to delete this entry? This action cannot be undone.')) {
            // Create payload for delete request
            const payload = {
                token: document.getElementById('token').value,
                collection: tableName,
                id: entryId
            };

            // Send delete request
            deleteEntry(payload);
        }
    });
}

// Function to send delete request to server
async function deleteEntry(payload) {
    hideAlerts();

    try {
        const response = await axios.post(`/{{@SITE.uri_backend}}/database/collections?delete-entry`, payload, {
            headers: {
                "Content-Type": "application/json",
            }
        });

        const data = response.data;
        if (data.status === "success") {
            alert(data.message);
            window.location.reload();
        } else {
            showAlert("error", data.message);
        }
    } catch (error) {
        showAlert("error", "Error deleting entry. Please try again later.");
        console.error("Error deleting entry:", error);
    } finally {
        // Close the modal
        let hideEntryModal = document.getElementById('editEntryExit');
        if (hideEntryModal) { hideEntryModal.click(); }
    }
}

// Function to send update request to server
async function updateEntry(formData, url) {
    event.preventDefault();
    hideAlerts();

    try {
        const response = await axios.post("/{{@SITE.uri_backend}}/" + url, formData, {
            headers: {
                "Content-Type": "application/json",
            },
        }).then(response => {
            const data = response.data;
            if (data.status === "success") {
                alert(response.data.message);
                window.location.reload();
                return false;
            } else {
                showAlert("error", response.data.message);
            }
        }).finally(() => {
            let hideEntryModal = document.getElementById('editEntryExit');
            if (hideEntryModal) { hideEntryModal.click(); }
        });

    } catch (error) {
        showAlert("error", "Error updating entry. Please try again later.");
    }
}

const editFieldsForm = document.getElementById('editFieldsForm');
const fieldsContainer = document.getElementById('fieldsContainer');
const addNewFieldBtn = document.getElementById('addNewField');

// Update data-primary attribute when checkbox toggled
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('field-primary-checkbox')) {
        const row = e.target.closest('.field-row');
        row.setAttribute('data-primary', e.target.checked ? 'true' : 'false');
    }
});

// Add new field dynamically (default not primary)
addNewFieldBtn.addEventListener('click', () => {
    const fieldHTML = `
      <div class="field-row" data-original-name="" data-primary="false">
        <input type="text" name="field_name[]" placeholder="Field name" required />
        <select name="field_type[]">
          <option value="TEXT">TEXT</option>
          <option value="INTEGER">INTEGER</option>
        </select>
        <label>
          <input type="checkbox" class="field-primary-checkbox">
          Primary
        </label>
        <button type="button" class="btn-remove-field">Remove</button>
      </div>`;
    fieldsContainer.insertAdjacentHTML('beforeend', fieldHTML);
});

// Mark a field as removed when clicking its remove button
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('btn-remove-field')) {
        e.preventDefault();
        const row = e.target.closest('.field-row');
        // Mark for deletion if it has an original name, else remove directly
        if (row.getAttribute('data-original-name')) {
            row.setAttribute('data-deleted', 'true');
            row.style.display = 'none';
        } else {
            // New rows can be removed directly
            row.remove();
        }
    }
});

// Submit edited fields (edit, add, delete + primary flag)
if (editFieldsForm) {
    editFieldsForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        const rows = document.querySelectorAll('.field-row');
        const fields = [];
        const removedFields = [];

        rows.forEach(row => {
            const isDeleted = row.getAttribute('data-deleted') === 'true';
            const original = row.dataset.originalName || null;
            const nameInput = row.querySelector('input[name="field_name[]"]');
            const typeSelect = row.querySelector('select[name="field_type[]"]');
            const primary = row.getAttribute('data-primary') === 'true';

            const name = nameInput?.value.trim();
            const type = typeSelect?.value;

            if (isDeleted && original) {
                removedFields.push(original);
                return;
            }
            if (!name) return; // Skip empty fields

            fields.push({ original, name, type, primary });
        });

        const payload = {
            token: token.value,
            collection: tableNameInput.value,
            fields: fields,
            removedFields: removedFields
        };

        try {
            const response = await axios.post("/{{@SITE.uri_backend}}/database/collections?update-fields", payload, {
                headers: { "Content-Type": "application/json" }
            });
            const data = response.data;
            if (data.status === "success") {
                alert(data.message);
                window.location.reload();
            } else {
                showAlert("error", data.message);
            }
        } catch (err) {
            showAlert("error", "Error updating fields.");
        }
    });
}

document.addEventListener('change', function(e) {
    if (e.target.classList.contains('field-primary-checkbox')) {
        if (e.target.checked) {
            // Uncheck all other primary checkboxes and update their rows
            document.querySelectorAll('.field-primary-checkbox').forEach(checkbox => {
                if (checkbox !== e.target) {
                    checkbox.checked = false;
                    const otherRow = checkbox.closest('.field-row');
                    otherRow.setAttribute('data-primary', 'false');
                }
            });
            // Mark current row as primary
            const row = e.target.closest('.field-row');
            row.setAttribute('data-primary', 'true');
        } else {
            // Uncheck current row primary flag
            const row = e.target.closest('.field-row');
            row.setAttribute('data-primary', 'false');
        }
    }
});

document.getElementById('renameForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const newTableName = document.getElementById('newTableName').value;
    const payload = {
        token: token.value,
        old_table_name: tableNameInput.value,
        new_table_name: newTableName
    };
    await axios.post("/{{@SITE.uri_backend}}/database/collections?rename-table", payload, {
        headers: {
            "Content-Type": "application/json"
        }
    }).then(response => {
        if(response.data.status === "success") {
            alert(response.data.message);
            window.location.href = "/{{@SITE.uri_backend}}/database/collections?view=" + newTableName;
        } else {
            showAlert("error", response.data.message);
        }
    }).catch(() => {
        showAlert("error", "Error renaming table.");
    });
});

document.getElementById('confirmDeleteBtn').addEventListener('click', async function() {
    if (window.confirm("Are you sure you want to delete this collection? This action cannot be undone.")) {
        const payload = {
            token: token.value,
            collection: tableNameInput.value
        };
        await axios.post("/{{@SITE.uri_backend}}/database/collections?delete-table", payload, {
            headers: { "Content-Type": "application/json" }
        }).then(response => {
            if(response.data.status === "success") {
                alert(response.data.message);
                window.location.href = "/{{@SITE.uri_backend}}/database";
            } else {
                showAlert("error", response.data.message);
            }
        }).catch(() => {
            showAlert("error", "Error deleting table.");
        });
    }
});

// Import form submission
const importForm = document.getElementById('importForm');
if (importForm) {
    importForm.addEventListener('submit', function(e) {
        e.preventDefault();

        // Check if file is selected
        const fileInput = document.getElementById('import_file');
        if (!fileInput.files || fileInput.files.length === 0) {
            alert('Please select a file to import.');
            return false;
        }

        // Check file type
        const file = fileInput.files[0];
        if (file.type !== 'application/json' && !file.name.endsWith('.json')) {
            alert('Please select a valid JSON file.');
            return false;
        }

        // Confirm import
        const importStrategy = document.querySelector('input[name="import_strategy"]:checked').value;
        let confirmMessage = 'Are you sure you want to import this data?';

        if (importStrategy === 'replace') {
            confirmMessage = 'WARNING: This will replace the existing collection with the imported data. All current data will be lost. Do you want to continue?';
        }

        if (!confirm(confirmMessage)) {
            return false;
        }

        // Create FormData object
        const formData = new FormData(importForm);
        formData.append('collection', document.getElementById('tbl_name').value);

        // Send import request
        importCollection(formData);
    });
}

// Function to send import request
async function importCollection(formData) {
    try {
        const response = await fetch('/{{@SITE.uri_backend}}/database/collections?import-collection', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.status === 'success') {
            alert(data.message);
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        alert('Error importing collection. Please try again later.');
        console.error('Import error:', error);
    }
}