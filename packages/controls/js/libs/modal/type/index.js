//@flow

export type ModalProps = {
	children?: any,
	visible?: boolean,
	onClose?: () => void,
	onRequestClose?: () => void,
	headerIcon?: any,
	headerTitle?: any,
	className?: string,
	size?: 'small' | 'medium' | 'large' | 'fill',
	isDismissible?: boolean,
	focusOnMount?: boolean | 'firstContentElement' | 'firstElement',
	props?: Object,
	style?: Object,
};
