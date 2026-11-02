import type { ClassificationPageProps } from './components/classifications/types';
import { ClassificationWorkspace } from './components/classifications/workspace';

export default function Brands(props: ClassificationPageProps) {
    return <ClassificationWorkspace {...props} kind="brands" />;
}
