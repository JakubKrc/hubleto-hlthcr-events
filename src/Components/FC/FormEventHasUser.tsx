import Translator from '@hubleto/react-ui/core/Translator';
import Form from '@hubleto/react-ui/components/fc/Form';
import { type FormProps } from '@hubleto/react-ui/components/fc/FormInterfaces';
import Input from '@hubleto/react-ui/components/fc/FormComponents/Input';

const componentName = 'FormEventHasUser';
const parentApp = 'Hubleto/App/External/WaiBlue/HlthcrEvents';
const T = new Translator(parentApp + '/Loader', 'Components/' + componentName);

const FormEventHasUser = (props: FormProps) => <Form
  componentName={componentName}
  parentApp={parentApp}
  model={parentApp + '/Models/EventHasUser'}
  urlSlug='hlthcr-events/event-has-users'
  title={{ sub: T.translate('Attendee of the event')}}
  tabs={{default: {content: () => <div className='grid grid-cols-1 md:grid-cols-2 gap-2'>
    <Input field='id_event' />
    <Input field='id_user' />
  </div>}}}
  {...props}
/>

export default FormEventHasUser;
