import Table from '@hubleto/react-ui/components/fc/Table'
import { type TableMeta, type TableProps } from '@hubleto/react-ui/components/fc/TableInterfaces'
import FormEvent from './FormEvent'

interface TableEventsProps extends TableProps {
  idCar?: number,
}

const componentName = 'TableEvents'
const parentApp = 'Hubleto/App/External/WaiBlue/HlthcrEvents'

// The events list (legacy "Rozvrh") and the bookings tab of the car form.
const TableEvents = (props: TableEventsProps) => {
  const idCar = props.idCar ?? 0;
  return <Table
    componentName={componentName}
    parentApp={parentApp}
    model={parentApp + '/Models/Event'}
    baseUrlSlug='hlthcr-events/events'
    endpointParams={{idCar: idCar}}
    formDefaultValues={idCar > 0 ? {id_car: idCar} : {}}
    formModalProps={{type: 'right wide'}}
    renderForm={(table: TableMeta) => <FormEvent {...table.getDefaultFormProps()} />}
    {...props}
  />;
}

export default TableEvents;
