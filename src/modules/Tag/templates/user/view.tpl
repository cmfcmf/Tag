<h2>{gt text='Tags on this site'}</h2>
<ul>
{foreach from=$tags item='tag'}
    <li><a href='{modurl modname="Tag" type="user" func="view" tag=$tag->getTag()|escape:'url'}'>{$tag->getTag()|safetext}</a></li>
{foreachelse}
    <li>{gt text='No tags.'}</li>
{/foreach}
</ul>
{if isset($result)}
<h3>{$selectedtag}</h3>
<ul>
{foreach from=$result item='r'}
<li><a href='{$r.url}'>{gt text="%s item (id# %s)" tag1=$r.module tag2=$r.objectId}</a></li>
{/foreach}
</ul>
{/if}