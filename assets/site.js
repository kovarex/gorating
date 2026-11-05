// The kpp version's script: assets/build.sh bundles this file and the modules it imports into one,
// minified and named by its contents (site.<hash>.js). The bundle's names are its own, so a function
// a page calls from an attribute (onclick="showEditDialog(event)") is put on window here.
import { showEditDialog } from "./editDialog.js";

window.showEditDialog = showEditDialog;
