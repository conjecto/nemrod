Building forms
=======

Create a form
-----------------
In your controller, create your form normally :
$form = $this->createForm(new FormType(), $resource);

Your form has to repect the parent form "resource_form" to use rdf resources.
You form should seems like this :

class FormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(...);
    }

    public function getName()
    {
        return 'form_name';
    }

    public function getParent()
    {
        return 'resource_form';
    }
}

Form types
-----------------
In your builderForm function, be carrefull to the form type
    $builder->add('foaf:name', 'text', array());
Indeed, the form type of property is not recognized like with doctrine.

You can use all form types you want (text, integer, date, ...). You can also use resource form type wich replace entity form type.

$builder->add('ex:property', 'resource', [
            'label' => 'Property',
            'expanded' => true,
            'multiple' => true,
            'required' => false,
            'class' => 'ex:property',
            'property' => 'rdf:label',
            'query_builder' => function (Repository $repo) {
                $qb = $repo->getQueryBuilder();
                $qb->construct();
                $qb->where('?s a ex:Property');
                return $qb;
            }
        ]);

The option property is used for the resource title display in the choiceList.
You can us the query builder to customize the query used to fill-in the resource list, in particular for request optimization.

The form in twig file
-----------------
{{ form_start(form) }}
form_widget(form['ex:property']
{{ form_rest(form) }}
<button type="submit" >Enregistrer</button>
{{ form_end(form) }}