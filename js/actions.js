function toggle(id)
{
    var elm = document.getElementById(id);
    
    if (elm.style.display == 'none')
    {
        elm.style.display = '';
    }
    else
    {
        elm.style.display = 'none';
    }
}
