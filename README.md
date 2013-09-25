grout
=====

### 0.1.0

-   **BUG**: Request. POST data hasn't been assigned correctly when passed via constructor.

-   **CHANGE**: ListContent. List won't be displayed anymore when editing has been disabled.

-   **CHANGE**: TextContent. Won't retun null anymore. Instead returns empty string.

-   **CHANGE**: App. Modules will now be initiated as soon as possible.

-   **FEATURE**: Added basic support for unit testing with PHPUnit

-   **CHANGE**: Changed some error messages

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
