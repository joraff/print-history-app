function toggle(id)
{
	row=document.getElementById('row'+id);
	box=document.getElementById('box'+id);
	classname=row.className;
	
	if (classname.search(/highlight/)>0)
	{
		row.className='row';
		box.checked='';
		
	}
	else
	{
		row.className='row highlight';
		box.checked='true';
	}				
}