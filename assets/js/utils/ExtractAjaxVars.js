function ExtractAjaxVars($script_element) {

    if (!$script_element.length)
        return;

    let data = {};

    try {
        data = JSON.parse($script_element.text());

    } catch (err) { // invalid json
        return null;
    }

    return data;
}

export default ExtractAjaxVars;