function PrintElem(elem)
{
    elem = document.getElementById(elem);
    divPrintPopup(elem.innerHTML);
}
function printRawDiv(id)
{
    var elem = document.getElementById(id);
    if (elem) divPrintPopup(elem.innerHTML);
}
function divPrintPopup(data)
{
    var mywindow = window.open('', 'Stampa', 'height=600,width=800');
    mywindow.document.write('<html><head>');
    mywindow.document.write('</head><body >');
    mywindow.document.write(data);
    mywindow.document.write('</body></html>');
    mywindow.document.close();
    mywindow.focus();
    mywindow.print();
    mywindow.close();

    return true;
}