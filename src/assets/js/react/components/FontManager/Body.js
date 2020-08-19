import React, { Component } from 'react'
import PropTypes from 'prop-types'
import { connect } from 'react-redux'
import SearchBox from './SearchBox'
import FontList from './FontList'
import AddFontName from './AddFontName'
import { getCustomFontList, addFont, editFont, deleteFont } from '../../actions/fontManager'

export class Body extends Component {
  static propTypes = {
    getCustomFontList: PropTypes.func.isRequired,
    match: PropTypes.object.isRequired,
    fontList: PropTypes.arrayOf(PropTypes.object),
    addFont: PropTypes.func.isRequired,
    history: PropTypes.object.isRequired
  }

  state = {
    label: '',
    fontStyles: {
      regular: '',
      italics: '',
      bold: '',
      bolditalics: ''
    }
  }

  componentDidMount () {
    this.props.getCustomFontList()
  }

  componentDidUpdate (prevProps, prevState, snapshot) {
    const { match: { params: { id } }, fontList, editFontSuccess } = this.props

    if (prevProps.match.params.id !== id && id) {
      this.handleRequestFontDetails()
    }

    if (prevProps.match.params.id !== id && !id) {
      this.handleRemoveFontDetails()
    }

    if (prevProps.fontList !== fontList && id && fontList) {
      this.handleRequestFontDetails()
    }

    if (prevProps.editFontSuccess !== editFontSuccess && editFontSuccess) {
      this.props.getCustomFontList()
    }
  }

  handleRequestFontDetails = () => {
    const { fontList, match: { params: { id } } } = this.props
    const font = fontList.filter(font => font.id === id)[0]

    this.setState({
      label: font.font_name,
      fontStyles: {
        regular: font.regular,
        italics: font.italics,
        bold: font.bold,
        bolditalics: font.bolditalics
      }
    })
  }

  handleRemoveFontDetails = () => {
    this.setState({
      label: '',
      fontStyles: {
        regular: '',
        italics: '',
        bold: '',
        bolditalics: ''
      }
    })
  }

  handleDeleteFont = (e, id) => {
    e.stopPropagation()
    const { deleteFont, history } = this.props

    if (window.confirm('Are you sure you want to delete this font?')) {
      deleteFont(id)
    }

    if (history.location.pathname !== '/fontmanager/') {
      history.push('/fontmanager/')
    }
  }

  handleDeleteFontStyle = (e, fontType) => {
    e.preventDefault()

    if (fontType === 'regular') {
      return window.alert('Unable to delete a required field. (Regular)')
    }

    this.state.fontStyles[fontType] = ''

    /* Render component */
    this.forceUpdate()
  }

  handleInputChange = e => {
    this.setState({ label: e.target.value })
  }

  handleUpload = e => {
    const { name, files } = e.target

    this.setState({
      fontStyles: {
        ...this.state.fontStyles,
        [name]: files[0]
      }
    })

    /* Reset file input value to enable next upload request */
    e.target.value = null
  }

  handleFontClick = fontId => {
    const { match, history } = this.props

    if (match.params.id === fontId) {
      return history.push('/fontmanager/')
    }

    history.push('/fontmanager/' + fontId)
  }

  handleAddFont = () => {
    const { label, fontStyles } = this.state

    if (!fontStyles.regular) {
      return window.alert('Regular font is required ***')
    }

    this.props.addFont({ label, ...fontStyles })

    /* Clean/refresh form fields after submission */
    this.handleRemoveFontDetails()
  }

  handleEditFont = id => {
    const { label, fontStyles } = this.state
    const data = {}

    /* Construct the data to be submitted */
    Object.keys(fontStyles).forEach(key => {
      if (typeof fontStyles[key] === 'object' || fontStyles[key] === '') {
        data[key] = fontStyles[key]
      }
    })

    const currentFont = this.props.fontList.filter(font => font.id === id)[0]
    const currentFontStyles = {
      regular: currentFont.regular,
      italics: currentFont.italics,
      bold: currentFont.bold,
      bolditalics: currentFont.bolditalics
    }

    /* Check if there's no changes in current font data */
    if (label === currentFont.font_name && JSON.stringify(fontStyles) === JSON.stringify(currentFontStyles)) {
      return
    }

    this.props.editFont({ id, font: { label, ...data } })
  }

  handleSubmit = e => {
    e.preventDefault()

    const { match: { params: { id } } } = this.props

    if (id) {
      return this.handleEditFont(id)
    }

    this.handleAddFont()
  }

  render () {
    return (
      <div className='wp-clearfix theme-about font-manager-body' id='gfpdf-font-manager-container'>
        <div>
          <SearchBox />

          <FontList
            onHandleFontClick={this.handleFontClick}
            onHandleDeleteFont={this.handleDeleteFont}
            {...this.props}
          />
        </div>

        <div>
          <AddFontName
            onHandleInputChange={this.handleInputChange}
            onHandleUpload={this.handleUpload}
            onHandleDeleteFontStyle={this.handleDeleteFontStyle}
            onHandleSubmit={this.handleSubmit}
            id={this.props.match.params.id}
            {...this.state}
          />
        </div>
      </div>
    )
  }
}

const mapStateToProps = state => ({
  fontList: state.fontManager.fontList,
  editFontSuccess: state.fontManager.editFontSuccess
})

export default connect(mapStateToProps, { getCustomFontList, addFont, editFont, deleteFont })(Body)
