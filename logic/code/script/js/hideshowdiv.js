function hideDiv(elem) 
{ 
    document.getElementById(elem).style.display = 'none'; 
}

function showDiv(elem)
{
    document.getElementById(elem).style.display = 'block'; 
}

function toggleDivVisibility(elem)
{
    if (document.getElementById(elem).style.display == 'none')
        document.getElementById(elem).style.display = 'block';
    else
        document.getElementById(elem).style.display = 'none';
} 

function setDivVisibility(elem, vis)
{
    document.getElementById(elem).style.display = vis ? 'block' : 'none';
    return vis ? true : false;
} 