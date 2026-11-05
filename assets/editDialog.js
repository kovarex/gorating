// The game's edit menu (the PHP version's common.js): shown under the button
// that was clicked, hidden by the next click.
export function showEditDialog(event)
{
  let element = document.getElementById('edit-dialog');
  if (element.style.display == 'none')
  {
    element.style.display = 'block';
    let button = event.target;
    let buttonPosition = button.getBoundingClientRect();
    element.style.left = buttonPosition.x + 'px';
    element.style.top = (buttonPosition.y + button.clientHeight) + 'px';
  }
  else
    element.style.display = 'none';
}
