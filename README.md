grout
=====

### 0.0.10

-   **CHANGE**: ArrayFilter->asFilter() now checks whether the key points to an array.

-   **CHANGE**: ImageTools::checkFile(). Errors will be suppressed with "@" now.

-   **CHANGE**: FileUpload::fromPhpFileArray(). Now will return null when no file has been selected (error = "4").

### 0.0.9

-   **BUG**: AdvancedForm didn't worked because of some API incompatibility.

-   **CHANGE**: AdvancedForm. $data wasn't declared previously. _createDataObject() now returns by default.

-   **CHANGE**: Form. _createDataObject() now returns by default.

-   **BUG**: DataStorage. clearAllStorages() and deleteAllStorages() didn't worked.

### 0.0.8

-   **DEPRECATED:** GroutQuick. Use r() instead of p() because p() stands for
    page which isn't the correct term anymore.

-   **FEATURE:** GroutQuick. Added er() and ea() which include escaping for r()
    and a()

### 0.0.7

-   **BREAKING:** $_GET variables "Grout_*" will be removed and passed to the
    request config.

-   **FEATURE:** Will be used DataStorage. Can be used to create directories for
    data storage on demand.

-   **FEATURE:** TemplateGenerator allows setting a custom class as template
    context.
